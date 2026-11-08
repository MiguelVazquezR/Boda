<?php

namespace Tests\Feature;

use App\Models\FloorPlanElement;
use App\Models\Guest;
use App\Models\GuestRelation;
use App\Models\GuestTable;
use App\Models\Invitation;
use App\Models\User;
use App\Models\WeddingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FloorPlanTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function guest(string $name, ?string $table = null, ?int $seat = null): Guest
    {
        [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, '');

        return Guest::create([
            'first_name' => $firstName,
            'last_name' => trim($lastName),
            'table_group' => $table,
            'seat_number' => $seat,
        ]);
    }

    public function test_el_plano_incluye_la_invitacion_de_cada_invitado(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $invitation = Invitation::create(['display_name' => 'Ana & Luis']);
        $ana = $this->guest('Ana López');
        $ana->update(['invitation_id' => $invitation->id]);

        $this->get(route('admin.floorplan.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/FloorPlan/Index')
                ->where('guests.0.full_name', 'Ana López')
                ->where('guests.0.invitation_id', $invitation->id)
                ->where('guests.0.invitation_name', 'Ana & Luis'));
    }

    public function test_el_admin_puede_crear_una_mesa_con_su_acomodo(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $this->postJson(route('admin.floorplan.tables.store'), [
            'name' => 'Mesa 1',
            'number' => 1,
            'shape' => 'round',
            'capacity' => 10,
            'x' => 300,
            'y' => 400,
            'width' => 180,
            'height' => 180,
            'rotation' => 0,
        ])->assertOk()->assertJsonPath('table.name', 'Mesa 1');

        $this->assertDatabaseHas('guest_tables', [
            'name' => 'Mesa 1',
            'capacity' => 10,
            'shape' => 'round',
            'x' => 300,
            'y' => 400,
        ]);
    }

    public function test_guardar_el_plano_mueve_invitados_al_renombrar_y_guarda_elementos(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $table = GuestTable::create(['name' => 'Mesa 1']);
        $guest = $this->guest('Ana López', 'Mesa 1');

        $response = $this->postJson(route('admin.floorplan.save'), [
            'venue' => [
                'title' => 'Salón Principal',
                'width' => 1500,
                'height' => 2200,
                'gridSize' => 20,
                'showGrid' => true,
                'snapToGrid' => false,
                'shape' => 'rect',
                'sections' => [],
            ],
            'tables' => [[
                'id' => $table->id,
                'name' => 'Mesa Norte',
                'number' => 1,
                'shape' => 'rect',
                'capacity' => 10,
                'x' => 200,
                'y' => 300,
                'width' => 240,
                'height' => 120,
                'rotation' => 0,
                'seat_distribution' => ['top' => 3, 'right' => 2, 'bottom' => 3, 'left' => 2],
            ]],
            'elements' => [[
                'id' => null,
                'kind' => 'bar',
                'name' => 'Barra de bebidas',
                'x' => 40,
                'y' => 60,
                'width' => 280,
                'height' => 90,
                'rotation' => 0,
                'color' => '#fce7f3',
            ]],
        ]);

        $response->assertOk()->assertJsonCount(1, 'elements');

        $this->assertDatabaseHas('guest_tables', [
            'id' => $table->id,
            'name' => 'Mesa Norte',
            'x' => 200,
            'y' => 300,
            'capacity' => 10,
        ]);
        $this->assertDatabaseHas('guests', ['id' => $guest->id, 'table_group' => 'Mesa Norte']);
        $this->assertDatabaseHas('floor_plan_elements', ['kind' => 'bar', 'name' => 'Barra de bebidas']);
        $this->assertSame('Salón Principal', WeddingSetting::current()->venue_layout['title']);

        // Al guardar de nuevo sin elementos, los que ya no están se eliminan.
        $this->postJson(route('admin.floorplan.save'), [
            'venue' => [
                'title' => 'Salón Principal',
                'width' => 1500,
                'height' => 2200,
                'gridSize' => 20,
                'showGrid' => true,
                'snapToGrid' => false,
                'shape' => 'rect',
                'sections' => [],
            ],
            'tables' => [[
                'id' => $table->id,
                'name' => 'Mesa Norte',
                'number' => 1,
                'shape' => 'rect',
                'capacity' => 10,
                'x' => 200,
                'y' => 300,
                'width' => 240,
                'height' => 120,
                'rotation' => 0,
                'seat_distribution' => ['top' => 3, 'right' => 2, 'bottom' => 3, 'left' => 2],
            ]],
            'elements' => [],
        ])->assertOk();

        $this->assertDatabaseCount('floor_plan_elements', 0);
    }

    public function test_no_permite_dejar_menos_asientos_que_invitados_sentados(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $table = GuestTable::create(['name' => 'Mesa 4']);
        $this->guest('Ana', 'Mesa 4', 1);
        $this->guest('Luis', 'Mesa 4', 2);

        $this->postJson(route('admin.floorplan.save'), [
            'venue' => [
                'title' => 'Salón',
                'width' => 1500,
                'height' => 2000,
                'gridSize' => 20,
                'showGrid' => true,
                'snapToGrid' => false,
                'sections' => [],
            ],
            'tables' => [[
                'id' => $table->id,
                'name' => 'Mesa 4',
                'number' => 4,
                'shape' => 'round',
                'capacity' => 1,
                'x' => 100,
                'y' => 100,
                'width' => 180,
                'height' => 180,
                'rotation' => 0,
            ]],
            'elements' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('tables');
    }

    public function test_se_pueden_intercambiar_los_nombres_de_dos_mesas(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $first = GuestTable::create(['name' => 'Mesa 1']);
        $second = GuestTable::create(['name' => 'Mesa 2']);
        $guest = $this->guest('Ana', 'Mesa 1', 1);

        $this->postJson(route('admin.floorplan.save'), [
            'venue' => [
                'title' => 'Salón',
                'width' => 1500,
                'height' => 2000,
                'gridSize' => 20,
                'showGrid' => true,
                'snapToGrid' => false,
                'sections' => [],
            ],
            'tables' => [
                [
                    'id' => $first->id,
                    'name' => 'Mesa 2',
                    'number' => 1,
                    'shape' => 'round',
                    'capacity' => 8,
                    'x' => 100,
                    'y' => 100,
                    'width' => 180,
                    'height' => 180,
                    'rotation' => 0,
                ],
                [
                    'id' => $second->id,
                    'name' => 'Mesa 1',
                    'number' => 2,
                    'shape' => 'round',
                    'capacity' => 8,
                    'x' => 400,
                    'y' => 100,
                    'width' => 180,
                    'height' => 180,
                    'rotation' => 0,
                ],
            ],
            'elements' => [],
        ])->assertOk();

        $this->assertSame('Mesa 2', $first->fresh()->name);
        $this->assertSame('Mesa 1', $second->fresh()->name);
        $this->assertSame('Mesa 2', $guest->fresh()->table_group);
    }

    public function test_asignar_un_asiento_libera_al_invitado_que_lo_ocupaba(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        GuestTable::create(['name' => 'Mesa 2']);
        $first = $this->guest('Ana', 'Mesa 2', 1);
        $second = $this->guest('Luis');

        $this->postJson(route('admin.floorplan.assign'), [
            'assignments' => [[
                'guest_id' => $second->id,
                'table_group' => 'Mesa 2',
                'seat_number' => 1,
            ]],
        ])->assertOk();

        $this->assertSame(1, $second->fresh()->seat_number);
        $this->assertSame('Mesa 2', $second->fresh()->table_group);
        $this->assertNull($first->fresh()->seat_number);
        $this->assertSame('Mesa 2', $first->fresh()->table_group);
    }

    public function test_quitar_la_mesa_deja_al_invitado_sin_asiento(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        GuestTable::create(['name' => 'Mesa 3']);
        $guest = $this->guest('Sofía', 'Mesa 3', 4);

        $this->postJson(route('admin.floorplan.unassign'), ['guest_ids' => [$guest->id]])->assertOk();

        $this->assertNull($guest->fresh()->table_group);
        $this->assertNull($guest->fresh()->seat_number);
    }

    public function test_se_pueden_crear_y_eliminar_vinculos_entre_invitados(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $ana = $this->guest('Ana');
        $luis = $this->guest('Luis');

        $response = $this->postJson(route('admin.floorplan.relations.store'), [
            'type' => 'couple',
            'guest_ids' => [$ana->id, $luis->id],
        ])->assertOk();

        $relation = GuestRelation::firstOrFail();
        $this->assertSame('couple', $relation->type);
        $response->assertJsonCount(1, 'relations');

        $this->deleteJson(route('admin.floorplan.relations.destroy', $relation->id))->assertOk();

        $this->assertDatabaseCount('guest_relations', 0);
    }

    public function test_alta_rapida_de_un_invitado_desde_el_plano(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $this->postJson(route('admin.floorplan.guests.store'), [
            'first_name' => 'María',
            'last_name' => 'Gómez',
        ])->assertOk()->assertJsonPath('guest.full_name', 'María Gómez');

        $this->assertDatabaseHas('guests', ['full_name' => 'María Gómez']);
    }

    public function test_solo_un_admin_puede_acceder_al_plano(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->get(route('admin.floorplan.index'))
            ->assertForbidden();
    }
}
