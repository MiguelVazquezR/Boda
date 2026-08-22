<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GuestCsvImportTest extends TestCase
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

    public function test_importa_csv_con_bom_punto_y_coma_y_encabezados_en_espanol(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        // BOM UTF-8 + delimitador ";" + encabezados en español
        $csv = "\xEF\xBB\xBFnombre;apellidos;edad;género;grupo;celular;origen;estado;ciudad;mesa\n"
            ."María;García López;30;Femenino;Familia;3312345678;Foráneo;Jalisco;Guadalajara;Mesa 1\n"
            ."Juan;Pérez;25;M;Amigos del Novio;55 8123-4567;local;Nuevo León;Monterrey;Mesa 2\n"
            ."Rosa;Martínez;;Hombre;;Local;Ciudad de México;CDMX;;\n"
            ."\n"
            ."Ana;López;30;F;Familia;7712345678;foráneo;Hidalgo;Pachuca;Mesa 3\n";

        $file = UploadedFile::fake()->createWithContent('invitados.csv', $csv);

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '4 invitado(s)'));

        $this->assertDatabaseCount('guests', 4);

        $maria = Guest::where('first_name', 'María')->first();
        $this->assertNotNull($maria);
        $this->assertSame('García López', $maria->last_name);
        $this->assertSame(30, $maria->age);
        $this->assertSame('femenino', $maria->gender);
        $this->assertSame('foraneo', $maria->origin);
        $this->assertSame('3312345678', $maria->phone);
        $this->assertSame('Jalisco', $maria->state);
        $this->assertSame('Guadalajara', $maria->city);
        $this->assertSame('Mesa 1', $maria->table_group);
        $this->assertSame('Familia', $maria->group->name);

        $juan = Guest::where('first_name', 'Juan')->first();
        $this->assertSame('masculino', $juan->gender);
        $this->assertSame('5581234567', $juan->phone);
        $this->assertSame('local', $juan->origin);
        $this->assertSame('Amigos del Novio', $juan->group->name);

        $rosa = Guest::where('first_name', 'Rosa')->first();
        $this->assertNull($rosa->age);
        $this->assertSame('masculino', $rosa->gender);
        $this->assertNull($rosa->phone);
        $this->assertNull($rosa->group);

        // El grupo "Familia" se crea una sola vez aunque aparezca dos veces
        $this->assertSame(1, GuestGroup::where('name', 'Familia')->count());
        $this->assertSame(2, GuestGroup::count());
    }

    public function test_importa_csv_con_coma_encabezados_ingles_y_full_name(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        $csv = "first_name,last_name,full_name,age,gender,group,phone,origin,state,city,table_group\n"
            ."Carlos,Hernández,,41,masculino,Amigos,5512345678,foraneo,Jalisco,Guadalajara,Mesa 5\n"
            .", ,Nombre Completo Sin Desglose,50,femenino,,,local,,,Mesa 6\n"
            ."Fila malformada sin columnas suficientes\n"
            .",,,,,\n";

        $file = UploadedFile::fake()->createWithContent('lista.csv', $csv);

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '2 invitado(s)')
            && str_contains($msg, '1 fila(s) omitida(s)'));

        $this->assertDatabaseCount('guests', 2);

        $carlos = Guest::where('first_name', 'Carlos')->first();
        $this->assertSame('Hernández', $carlos->last_name);
        $this->assertSame(41, $carlos->age);
        $this->assertSame('Amigos', $carlos->group->name);

        $nombres = Guest::where('full_name', 'Nombre Completo Sin Desglose')->first();
        $this->assertNotNull($nombres);
        $this->assertSame('Nombre', $nombres->first_name);
        $this->assertSame('Completo Sin Desglose', $nombres->last_name);
        $this->assertSame('femenino', $nombres->gender);
    }

    public function test_importa_csv_codificado_en_cp850(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        // Simula un CSV exportado por Excel en Windows con codificación CP850 (bytes 0x82 = é, etc.)
        $utf8 = "first_name,last_name,age,gender\n"
            ."José,Rodríguez González,30,masculino\n"
            ."María,García López,25,femenino\n";
        $csv = mb_convert_encoding($utf8, 'CP850', 'UTF-8');

        $file = UploadedFile::fake()->createWithContent('lista_cp850.csv', $csv);

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, '2 invitado(s)'));

        $this->assertDatabaseCount('guests', 2);

        $jose = Guest::where('first_name', 'José')->first();
        $this->assertNotNull($jose);
        $this->assertSame('Rodríguez González', $jose->last_name);
        $this->assertSame('jose rodriguez gonzalez', $jose->search_slug);

        $maria = Guest::where('first_name', 'María')->first();
        $this->assertNotNull($maria);
        $this->assertSame('García López', $maria->last_name);
    }

    public function test_importa_csv_codificado_en_windows1252(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        // Windows-1252 (ANSI de Windows): "José" usa el byte 0xE9 para la é
        $utf8 = "first_name,last_name\n"
            ."José,Álvarez Núñez\n";
        $csv = mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');

        $file = UploadedFile::fake()->createWithContent('lista_win1252.csv', $csv);

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $jose = Guest::where('first_name', 'José')->first();
        $this->assertNotNull($jose);
        $this->assertSame('Álvarez Núñez', $jose->last_name);
    }

    public function test_importa_csv_codificado_en_utf16(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        // Excel "Texto Unicode" usa UTF-16 LE con BOM
        $utf8 = "first_name,last_name\n"
            ."María,Hernández Sánchez\n";
        $csv = "\xFF\xFE".mb_convert_encoding($utf8, 'UTF-16LE', 'UTF-8');

        $file = UploadedFile::fake()->createWithContent('lista_utf16.csv', $csv);

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $maria = Guest::where('first_name', 'María')->first();
        $this->assertNotNull($maria);
        $this->assertSame('Hernández Sánchez', $maria->last_name);
    }

    public function test_muestra_error_si_falta_columna_de_nombre(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        $csv = "email,phone\ncorreo@ejemplo.com,3312345678\n";

        $file = UploadedFile::fake()->createWithContent('sin_nombre.csv', $csv);

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('guests', 0);
    }

    public function test_no_permite_importar_a_usuarios_no_admin(): void
    {
        $user = $this->regularUser();
        $this->actingAs($user, 'sanctum');

        $csv = "first_name,last_name\nAna,Martínez\n";

        $file = UploadedFile::fake()->createWithContent('lista.csv', $csv);

        $this->post(route('admin.guests.import'), ['csv_file' => $file])
            ->assertForbidden();

        $this->assertDatabaseCount('guests', 0);
    }

    public function test_rechaza_archivos_que_no_son_csv(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user, 'sanctum');

        $file = UploadedFile::fake()->createWithContent('datos.pdf', '%PDF-1.4 test');

        $response = $this->post(route('admin.guests.import'), ['csv_file' => $file]);

        $response->assertSessionHasErrors('csv_file');
        $this->assertDatabaseCount('guests', 0);
    }
}
