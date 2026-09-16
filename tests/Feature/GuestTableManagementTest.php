<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuestTableManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function regularUser(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function guest(string $firstName, ?string $table = null): Guest
    {
        return Guest::create([
            'first_name' => $firstName,
            'last_name' => 'Pérez',
            'table_group' => $table,
        ]);
    }

    public function test_crea_una_mesa_nueva(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $response = $this->post(route('admin.tables.store'), ['name' => 'Mesa 7']);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'Mesa 7'));

        $this->assertDatabaseHas('guest_tables', ['name' => 'Mesa 7']);
    }

    public function test_no_permite_crear_una_mesa_duplicada(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        GuestTable::create(['name' => '5']);

        $this->post(route('admin.tables.store'), ['name' => '5'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, GuestTable::where('name', '5')->count());
    }

    public function test_el_panel_lista_las_mesas_incluidas_las_vacias(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $this->guest('Ana', '5');
        $this->guest('Luis', '5');
        GuestTable::create(['name' => 'Mesa 9']); // mesa sin invitados

        $this->get(route('admin.tables.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Tables/Index')
                ->has('tables', 2)
                ->where('tables.0.name', '5')
                ->where('tables.0.total', 2)
                ->where('tables.1.name', 'Mesa 9')
                ->where('tables.1.total', 0)
                ->where('stats.tables', 2)
                ->where('stats.assigned', 2)
                ->where('stats.unassigned', 0));
    }

    public function test_renombrar_una_mesa_mueve_a_sus_invitados(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $table = GuestTable::create(['name' => '5']);
        $this->guest('Ana', '5');
        $this->guest('Luis', '5');
        $this->guest('Sofía'); // sin mesa

        $response = $this->put(route('admin.tables.update', $table->id), ['name' => 'Familia López']);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'Familia López'));

        $this->assertDatabaseHas('guest_tables', ['id' => $table->id, 'name' => 'Familia López']);
        $this->assertSame(2, Guest::where('table_group', 'Familia López')->count());
        $this->assertSame(0, Guest::where('table_group', '5')->count());
        $this->assertNull(Guest::where('first_name', 'Sofía')->first()->table_group);
    }

    public function test_no_permite_renombrar_a_una_mesa_ya_existente(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $table = GuestTable::create(['name' => '5']);
        GuestTable::create(['name' => '7']);
        $this->guest('Ana', '5');
        $this->guest('Luis', '7');

        $this->put(route('admin.tables.update', $table->id), ['name' => '7'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('guest_tables', ['id' => $table->id, 'name' => '5']);
        $this->assertSame(1, Guest::where('table_group', '5')->count());
        $this->assertSame(1, Guest::where('table_group', '7')->count());
    }

    public function test_eliminar_una_mesa_desasigna_a_sus_invitados(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $table = GuestTable::create(['name' => '5']);
        $this->guest('Ana', '5');
        $this->guest('Luis', '5');

        $response = $this->delete(route('admin.tables.destroy', $table->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '2 invitado(s)'));

        $this->assertDatabaseMissing('guest_tables', ['id' => $table->id]);
        $this->assertSame(2, Guest::whereNull('table_group')->count());
    }

    public function test_asignar_mesa_registra_la_mesa_en_el_catalogo(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ana = $this->guest('Ana', '5');
        $luis = $this->guest('Luis', '7');

        $this->post(route('admin.tables.assign'), [
            'guest_ids' => [$ana->id, $luis->id],
            'table_group' => 'Mesa 3',
        ])->assertRedirect()->assertSessionHas('success');

        // Los invitados con mesa previa se mueven a la última mesa asignada
        $this->assertSame(2, Guest::where('table_group', 'Mesa 3')->count());
        $this->assertDatabaseHas('guest_tables', ['name' => 'Mesa 3']);
    }

    public function test_no_permite_gestionar_mesas_a_usuarios_no_admin(): void
    {
        $table = GuestTable::create(['name' => '5']);

        $this->actingAs($this->regularUser(), 'sanctum');

        $this->post(route('admin.tables.store'), ['name' => 'Mesa 7'])->assertForbidden();
        $this->put(route('admin.tables.update', $table->id), ['name' => 'Mesa 8'])->assertForbidden();
        $this->delete(route('admin.tables.destroy', $table->id))->assertForbidden();

        $this->assertDatabaseHas('guest_tables', ['name' => '5']);
        $this->assertDatabaseMissing('guest_tables', ['name' => 'Mesa 7']);
    }
}
