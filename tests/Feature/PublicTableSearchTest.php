<?php

namespace Tests\Feature;

use App\Models\Guest;
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

    public function test_no_expone_invitados_sin_mesa_asignada(): void
    {
        $this->guest('Ana Pérez');           // sin mesa
        $this->guest('Ana Gómez', 'Mesa 3'); // con mesa

        $response = $this->getJson('/mesas/buscar?q=ana');

        $response->assertOk()->assertJsonCount(1);
        $this->assertSame('Ana Gómez', $response->json('0.full_name'));
    }

    public function test_requiere_al_menos_dos_caracteres(): void
    {
        $this->getJson('/mesas/buscar?q=a')
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }
}
