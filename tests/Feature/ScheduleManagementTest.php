<?php

namespace Tests\Feature;

use App\Models\ScheduleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function item(array $attributes = []): ScheduleItem
    {
        return ScheduleItem::create(array_merge([
            'time' => '19:00',
            'title' => 'Bienvenida',
            'description' => 'Cócteles de recibimiento',
            'sort_order' => 1,
            'is_active' => true,
        ], $attributes));
    }

    public function test_editar_un_momento_conserva_y_actualiza_el_titulo(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $item = $this->item();

        // El panel envía la edición como POST con _method=put porque el
        // formulario es multipart (permite subir imagen).
        $this->post(route('admin.schedule.update', $item->id), [
            '_method' => 'put',
            'time' => '20:00',
            'title' => 'Bienvenida y cócteles',
            'description' => 'Actualizado',
            'is_active' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();

        $this->assertSame('Bienvenida y cócteles', $item->title);
        $this->assertSame('20:00', $item->time);
        $this->assertSame('Actualizado', $item->description);
    }

    public function test_editar_un_momento_permite_reemplazar_la_imagen(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('schedule/anterior.jpg', 'anterior');

        $this->actingAs($this->adminUser(), 'sanctum');

        $item = $this->item(['image_path' => 'schedule/anterior.jpg']);

        $this->post(route('admin.schedule.update', $item->id), [
            '_method' => 'put',
            'title' => 'Bienvenida',
            'image' => UploadedFile::fake()->image('nueva.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $item->refresh();

        $this->assertNotSame('schedule/anterior.jpg', $item->image_path);
        Storage::disk('public')->assertExists($item->image_path);
        Storage::disk('public')->assertMissing('schedule/anterior.jpg');
    }

    public function test_el_titulo_sigue_siendo_obligatorio_al_editar(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $item = $this->item();

        $this->post(route('admin.schedule.update', $item->id), [
            '_method' => 'put',
            'title' => '',
        ])->assertSessionHasErrors('title');

        $this->assertSame('Bienvenida', $item->fresh()->title);
    }
}
