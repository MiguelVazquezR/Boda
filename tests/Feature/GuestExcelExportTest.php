<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class GuestExcelExportTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_exporta_los_invitados_y_su_mesa_en_un_archivo_excel(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        Guest::create(['first_name' => 'María', 'last_name' => 'García López', 'table_group' => 'Mesa 2']);
        Guest::create(['first_name' => 'Juan', 'last_name' => 'Pérez', 'table_group' => 'Mesa 1']);
        Guest::create(['first_name' => 'Rosa', 'last_name' => 'Martínez']);

        $response = $this->get(route('admin.guests.export'));

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            $response->headers->get('content-type'),
        );
        $this->assertStringContainsString('invitados-', $response->headers->get('content-disposition'));

        $sheet = $this->sheetXml($response->streamedContent());

        $this->assertStringContainsString('Invitado', $sheet);
        $this->assertStringContainsString('Mesa', $sheet);
        $this->assertStringContainsString('María García López', $sheet);
        $this->assertStringContainsString('Mesa 2', $sheet);
        $this->assertStringContainsString('Juan Pérez', $sheet);
        $this->assertStringContainsString('Mesa 1', $sheet);
        $this->assertStringContainsString('Sin asignar', $sheet);

        // Las mesas se listan en orden natural y los invitados sin mesa van al final
        $this->assertLessThan(strpos($sheet, 'Mesa 2'), strpos($sheet, 'Mesa 1'));
        $this->assertLessThan(strpos($sheet, 'Sin asignar'), strpos($sheet, 'Mesa 2'));
    }

    public function test_no_permite_exportar_a_usuarios_no_admin(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user, 'sanctum');

        $this->get(route('admin.guests.export'))->assertForbidden();
    }

    /**
     * Abre el .xlsx descargado y devuelve el XML de la primera hoja.
     */
    private function sheetXml(string $xlsx): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test');
        file_put_contents($path, $xlsx);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true);

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        $this->assertIsString($sheet);

        return $sheet;
    }
}
