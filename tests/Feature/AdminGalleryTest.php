<?php

namespace Tests\Feature;

use App\Models\GalleryPhoto;
use App\Models\User;
use App\Models\WeddingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function photo(string $status): GalleryPhoto
    {
        return GalleryPhoto::create([
            'image_path' => "gallery/{$status}-" . uniqid() . '.jpg',
            'status' => $status,
        ]);
    }

    public function test_el_admin_puede_aprobar_todas_las_fotos_pendientes(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $this->photo('pending');
        $this->photo('pending');
        $this->photo('rejected');

        $this->patch(route('admin.gallery.approve-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, GalleryPhoto::pending()->count());
        $this->assertSame(2, GalleryPhoto::approved()->count());
        $this->assertSame(1, GalleryPhoto::where('status', 'rejected')->count());
    }

    public function test_el_admin_puede_activar_y_desactivar_la_publicacion_sin_aprobacion(): void
    {
        $this->actingAs($this->adminUser(), 'sanctum');

        $this->patch(route('admin.gallery.auto-approve'), ['auto_approve' => true])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertTrue(WeddingSetting::current()->gallery_auto_approve);

        $this->patch(route('admin.gallery.auto-approve'), ['auto_approve' => false])
            ->assertRedirect();
        $this->assertFalse(WeddingSetting::current()->gallery_auto_approve);
    }

    public function test_un_usuario_que_no_es_admin_no_puede_aprobar_todas_las_fotos(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'sanctum')
            ->patch(route('admin.gallery.approve-all'))
            ->assertForbidden();
    }
}
