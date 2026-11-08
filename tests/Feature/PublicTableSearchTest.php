<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestTable;
use App\Models\Invitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTableSearchTest extends TestCase
{
    use RefreshDatabase;

    private function guest(string $fullName, ?string $table = null): Guest
    {
        [$firstName, $lastName] = array_pad(explode(' ', $fullName, 2), 2, '');

        return Guest::create([
            'first_name' => $firstName,
            'last_name' => trim($lastName),
            'table_group' => $table,
        ]);
    }

    public function test_devuelve_la_mesa_y_los_demas_invitados_asignados(): void
    {
        $ana = $this->guest('Ana Pérez', '5');
        $this->guest('Luis Pérez', '5');
        $this->guest('Sofía Pérez', '5');
        $this->guest('Pedro Ramírez', '7'); // otra mesa: no debe aparecer

        $response = $this->getJson('/mesas/buscar?q=ana');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'id' => $ana->id,
                    'full_name' => 'Ana Pérez',
                    'table_group' => '5',
                    'tablemates' => ['Luis Pérez', 'Sofía Pérez'],
                ],
            ]);
    }

    public function test_los_companeros_no_incluyen_al_invitado_buscado(): void
    {
        $this->guest('Ana Pérez', '5');
        $this->guest('Luis Pérez', '5');

        $response = $this->getJson('/mesas/buscar?q=ana');

        $response->assertOk();
        $this->assertSame(['Luis Pérez'], $response->json('0.tablemates'));
    }

    public function test_una_mesa_sin_mas_invitados_devuelve_companeros_vacios(): void
    {
        $this->guest('Ana Pérez', '5');
        $this->guest('Pedro Ramírez', '7');

        $response = $this->getJson('/mesas/buscar?q=ana');

        $response->assertOk()->assertJson(['0' => ['tablemates' => []]]);
    }

    public function test_incluye_invitados_sin_mesa_marcados_como_pendientes(): void
    {
        $this->guest('Ana Pérez');           // sin mesa
        $this->guest('Ana Gómez', 'Mesa 3'); // con mesa

        $response = $this->getJson('/mesas/buscar?q=ana');

        $response->assertOk()->assertJsonCount(2);

        $results = collect($response->json())->keyBy('full_name');

        $this->assertFalse($results['Ana Pérez']['has_table']);
        $this->assertTrue($results['Ana Gómez']['has_table']);
    }

    public function test_los_invitados_con_mesa_incluyen_el_link_del_plano_de_su_invitacion(): void
    {
        $invitation = Invitation::create(['display_name' => 'Ana & Luis']);

        $ana = $this->guest('Ana Pérez', 'Mesa 3');
        $ana->update(['invitation_id' => $invitation->id]);

        $this->getJson('/mesas/buscar?q=ana')
            ->assertOk()
            ->assertJsonPath('0.table_plan_url', route('invitation.table', $invitation->token));
    }

    public function test_devuelve_el_numero_de_asiento(): void
    {
        $ana = $this->guest('Ana Pérez', 'Mesa 5');
        $ana->update(['seat_number' => 7]);

        $this->getJson('/mesas/buscar?q=ana')
            ->assertOk()
            ->assertJsonPath('0.seat_number', 7);
    }

    public function test_requiere_al_menos_dos_caracteres(): void
    {
        $this->getJson('/mesas/buscar?q=a')
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_el_plano_resalta_al_invitado_y_a_su_pareja_de_la_misma_invitacion(): void
    {
        $table = GuestTable::create([
            'name' => 'Mesa 5',
            'capacity' => 8,
            'shape' => 'round',
            'x' => 120,
            'y' => 120,
        ]);

        $invitation = Invitation::create(['display_name' => 'Ana & Luis']);

        $ana = $this->guest('Ana Pérez', 'Mesa 5');
        $ana->update(['invitation_id' => $invitation->id, 'seat_number' => 3]);

        $luis = $this->guest('Luis Pérez', 'Mesa 5');
        $luis->update(['invitation_id' => $invitation->id, 'seat_number' => 4]);

        // Otro invitado en la misma mesa, de otra invitación: no debe resaltarse.
        $pedro = $this->guest('Pedro Ramírez', 'Mesa 5');
        $pedro->update(['seat_number' => 5]);

        $response = $this->getJson(route('tables.plan', $ana));

        $response->assertOk()
            ->assertJsonPath('guest.full_name', 'Ana Pérez')
            ->assertJsonPath('venue.width', 1500)
            ->assertJsonCount(1, 'tables')
            ->assertJsonFragment([
                'table_id' => $table->id,
                'seat_number' => 3,
                'guest_name' => 'Ana Pérez',
                'is_primary' => true,
            ])
            ->assertJsonFragment([
                'table_id' => $table->id,
                'seat_number' => 4,
                'guest_name' => 'Luis Pérez',
                'is_primary' => false,
            ]);

        $seats = $response->json('seats');
        $this->assertCount(2, $seats);
        $this->assertNotContains('Pedro Ramírez', array_column($seats, 'guest_name'));
    }

    public function test_el_plano_resalta_la_mesa_si_el_invitado_no_tiene_asiento(): void
    {
        $table = GuestTable::create(['name' => 'Mesa 5']);

        $ana = $this->guest('Ana Pérez', 'Mesa 5');

        $this->getJson(route('tables.plan', $ana))
            ->assertOk()
            ->assertJsonPath('seats', [])
            ->assertJsonPath('highlight_tables', [$table->id]);
    }

    public function test_el_plano_no_existe_para_invitados_sin_mesa(): void
    {
        $ana = $this->guest('Ana Pérez');

        $this->getJson(route('tables.plan', $ana))->assertNotFound();
    }
}
