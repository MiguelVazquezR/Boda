<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edición rápida de celdas desde la tabla de invitados
 * (origen, celular, grupo y mesa) sin abrir el formulario completo.
 */
class GuestInlineEditTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function guest(): Guest
    {
        return Guest::create([
            'first_name' => 'Ana',
            'last_name' => 'Pérez',
        ]);
    }

    private function updateCell(Guest $guest, string $field, mixed $value)
    {
        return $this->patch(route('admin.guests.cell', $guest->id), [
            'field' => $field,
            'value' => $value,
        ]);
    }

    public function test_actualiza_el_origen_desde_la_tabla(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'origin', 'foraneo')->assertRedirect();

        $this->assertSame('foraneo', $guest->fresh()->origin);
    }

    public function test_actualiza_el_genero_desde_la_tabla(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'gender', 'femenino')->assertRedirect();

        $this->assertSame('femenino', $guest->fresh()->gender);
    }

    public function test_rechaza_un_genero_invalido(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'gender', 'otro')->assertSessionHasErrors('value');

        $this->assertNull($guest->fresh()->gender);
    }

    public function test_actualiza_celular_grupo_y_mesa(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();
        $group = GuestGroup::create(['name' => 'Familia novia']);

        $this->updateCell($guest, 'phone', '3312345678')->assertRedirect();
        $this->updateCell($guest, 'guest_group_id', $group->id)->assertRedirect();
        $this->updateCell($guest, 'table_group', 'Mesa 3')->assertRedirect();

        $guest->refresh();

        $this->assertSame('3312345678', $guest->phone);
        $this->assertSame($group->id, $guest->guest_group_id);
        $this->assertSame('Mesa 3', $guest->table_group);
        // La mesa usada se registra en el catálogo de mesas
        $this->assertDatabaseHas('guest_tables', ['name' => 'Mesa 3']);
    }

    public function test_permite_quitar_el_celular_el_grupo_y_la_mesa(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();
        $group = GuestGroup::create(['name' => 'Amigos']);

        $guest->update([
            'phone' => '3312345678',
            'guest_group_id' => $group->id,
            'table_group' => 'Mesa 9',
        ]);

        $this->updateCell($guest, 'phone', '')->assertRedirect();
        $this->updateCell($guest, 'guest_group_id', null)->assertRedirect();
        $this->updateCell($guest, 'table_group', '')->assertRedirect();

        $guest->refresh();

        $this->assertNull($guest->phone);
        $this->assertNull($guest->guest_group_id);
        $this->assertNull($guest->table_group);
    }

    public function test_rechaza_un_celular_que_no_tiene_diez_digitos(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'phone', '123')->assertSessionHasErrors('value');

        $this->assertNull($guest->fresh()->phone);
    }

    public function test_rechaza_un_origen_invalido(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'origin', 'marciano')->assertSessionHasErrors('value');

        $this->assertNull($guest->fresh()->origin);
    }

    public function test_rechaza_un_grupo_que_no_existe(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'guest_group_id', 999)->assertSessionHasErrors('value');

        $this->assertNull($guest->fresh()->guest_group_id);
    }

    public function test_no_permite_editar_campos_fuera_de_la_lista(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');
        $guest = $this->guest();

        $this->updateCell($guest, 'first_name', 'Mario')->assertSessionHasErrors('field');

        $this->assertSame('Ana', $guest->fresh()->first_name);
    }

    public function test_un_usuario_normal_no_puede_editar_celdas(): void
    {
        $regular = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $guest = $this->guest();

        $this->actingAs($regular, 'sanctum');

        $this->updateCell($guest, 'origin', 'local')->assertForbidden();

        $this->assertNull($guest->fresh()->origin);
    }
}
