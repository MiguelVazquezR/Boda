<?php

namespace Tests\Feature;

use App\Models\GalleryPhoto;
use App\Models\WeddingSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_upload_multiple_photos_for_moderation(): void
    {
        Storage::fake('public');

        $response = $this->post(route('gallery.store'), [
            'uploader_name' => 'Invitada',
            'images' => [
                UploadedFile::fake()->image('momento-1.jpg'),
                UploadedFile::fake()->image('momento-2.jpg'),
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('gallery_photos', 2);
        $this->assertDatabaseHas('gallery_photos', [
            'uploader_name' => 'Invitada',
            'status' => 'pending',
        ]);

        foreach (GalleryPhoto::all() as $photo) {
            Storage::disk('public')->assertExists($photo->image_path);
        }
    }

    public function test_photos_are_published_immediately_when_auto_approval_is_enabled(): void
    {
        Storage::fake('public');
        WeddingSetting::current()->update(['gallery_auto_approve' => true]);

        $this->post(route('gallery.store'), [
            'images' => [UploadedFile::fake()->image('momento.jpg')],
        ])->assertRedirect()->assertSessionHas('success');

        $photo = GalleryPhoto::firstOrFail();
        $this->assertSame('approved', $photo->status);

        $this->get(route('gallery.show'))
            ->assertInertia(fn (Assert $page) => $page->has('galleryPhotos', 1));

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('galleryRequiresApproval', false));
    }

    public function test_the_public_gallery_shows_approved_photos_and_allows_downloads(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('gallery/approved.jpg', 'approved image');

        $approvedPhoto = GalleryPhoto::create([
            'image_path' => 'gallery/approved.jpg',
            'uploader_name' => 'Invitada',
            'status' => 'approved',
        ]);
        GalleryPhoto::create([
            'image_path' => 'gallery/pending.jpg',
            'status' => 'pending',
        ]);

        $this->get(route('gallery.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Gallery/Index')
                ->has('galleryPhotos', 1)
                ->where('galleryPhotos.0.id', $approvedPhoto->id));

        $this->get(route('gallery.download', $approvedPhoto))
            ->assertOk()
            ->assertDownload('approved.jpg');
    }

    public function test_pending_photos_cannot_be_downloaded_from_the_public_gallery(): void
    {
        $photo = GalleryPhoto::create([
            'image_path' => 'gallery/pending.jpg',
            'status' => 'pending',
        ]);

        $this->get(route('gallery.download', $photo))->assertNotFound();
    }
}
