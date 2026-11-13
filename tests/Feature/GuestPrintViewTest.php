<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestPrintViewTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_muestra_la_lista_agrupada_por_mesa_lista_para_imprimir(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        Guest::create(['first_name' => 'María', 'last_name' => 'García López', 'table_group' => 'Mesa 2']);
        Guest::create(['first_name' => 'Juan', 'last_name' => 'Pérez', 'table_group' => 'Mesa 1']);
        Guest::create(['first_name' => 'Rosa', 'last_name' => 'Martínez']);

        $response = $this->get(route('admin.guests.print'));

        $response->assertOk();
        $response->assertViewIs('admin.guests.print');

        // Nombres y mesas, ordenados por mesa y con los invitados sin mesa al final
        $response->assertSeeInOrder(['Mesa 1', 'Juan Pérez', 'Mesa 2', 'María García López', 'Sin asignar', 'Rosa Martínez']);
        $response->assertSee('Imprimir / Guardar PDF');
        $response->assertSee('window.print()', false);

        // Resumen: 3 invitados, 2 mesas y 1 sin mesa
        $response->assertViewHas('totalGuests', 3);
        $response->assertViewHas('totalTables', 2);
        $response->assertViewHas('unassignedCount', 1);
    }

    public function test_no_permite_ver_la_vista_a_usuarios_no_admin(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user, 'sanctum');

        $this->get(route('admin.guests.print'))->assertForbidden();
    }
}
