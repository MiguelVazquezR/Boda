# Image Upload Diagnosis — Boda Project

> **Generated:** 2026-07-13  
> **Project:** Laravel 12 + Inertia.js 2 + Vue 3  
> **Purpose:** Document the current state of image handling to diagnose why uploaded images are not being saved/displayed correctly.

---

## 1. Backend — Gallery

### 1.1 Migration: `create_gallery_photos_table`

**File:** `database/migrations/2026_07_12_200400_create_gallery_photos_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('uploader_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
    }
};
```

**Column for image path:** `image_path` (type: `string`).

---

### 1.2 Model: `GalleryPhoto`

**File:** `app/Models/GalleryPhoto.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    protected $table = 'gallery_photos';

    protected $fillable = [
        'image_path',
        'uploader_name',
        'status',
    ];

    /**
     * Scope: solo fotos aprobadas.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: solo fotos pendientes de moderación.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Accessor: URL pública completa de la imagen.
     */
    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
```

**Accessor details:**
- `image_url` is built via `Storage::disk('public')->url($this->image_path)`.
- With `APP_URL=http://localhost:8000` and `config/filesystems.php`'s public disk URL = `'{APP_URL}/storage'`, the result would be:  
  `http://localhost:8000/storage/gallery/<hashed_filename>.jpg`
- No `asset()` is used; purely `Storage::url()`.
- The accessor is never appended to `$appends` — it relies on Laravel's automatic serialization for Eloquent accessors (which works by default in Laravel when the model is serialized to JSON/array via Inertia).

---

### 1.3 Public Gallery Controller (`GalleryController@store`)

**File:** `app/Http/Controllers/GalleryController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGalleryPhotoRequest;
use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * Lista de fotos aprobadas (puede usarse como endpoint independiente
     * o consumirse desde el prop de PageController@home).
     */
    public function index()
    {
        return response()->json(
            GalleryPhoto::approved()->latest()->get()
        );
    }

    /**
     * Subida de nueva foto por un invitado (queda en estado pending).
     */
    public function store(StoreGalleryPhotoRequest $request)
    {
        $path = $request->file('image')->store('gallery', 'public');

        GalleryPhoto::create([
            'image_path' => $path,
            'uploader_name' => $request->input('uploader_name'),
            'status' => 'pending',
        ]);

        return back()->with('success', '¡Tu foto se ha subido correctamente! Será visible una vez aprobada.');
    }
}
```

**Storage details:**
- Disk: `'public'` (→ `storage/app/public/`)
- Directory: `'gallery'` (→ `storage/app/public/gallery/`)
- Saved to DB column: `image_path` gets the relative path returned by `store()` (e.g., `gallery/abc123.jpg`).
- Validation: via `StoreGalleryPhotoRequest` — requires `image`, max 10MB, mimes: jpg/jpeg/png/webp.

---

### 1.4 Admin Gallery Controller

**File:** `app/Http/Controllers/Admin/GalleryController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    /**
     * Panel de moderación: muestra fotos filtrables por estado.
     */
    public function index()
    {
        return Inertia::render('Admin/Gallery/Index', [
            'photos' => GalleryPhoto::query()
                ->when(request()->query('status'), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'counts' => [
                'pending' => GalleryPhoto::pending()->count(),
                'approved' => GalleryPhoto::approved()->count(),
                'rejected' => GalleryPhoto::where('status', 'rejected')->count(),
            ],
        ]);
    }

    /**
     * Aprobar una foto.
     */
    public function approve(GalleryPhoto $photo)
    {
        $photo->update(['status' => 'approved']);

        return back()->with('success', 'Foto aprobada.');
    }

    /**
     * Rechazar una foto.
     */
    public function reject(GalleryPhoto $photo)
    {
        $photo->update(['status' => 'rejected']);

        return back()->with('success', 'Foto rechazada.');
    }

    /**
     * Eliminar una foto (archivo + registro).
     */
    public function destroy(GalleryPhoto $photo)
    {
        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        return back()->with('success', 'Foto eliminada permanentemente.');
    }

    /**
     * Subida de fotos desde el panel admin (auto-aprobadas).
     */
    public function store()
    {
        request()->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $count = 0;
        foreach (request()->file('images') as $file) {
            GalleryPhoto::create([
                'image_path' => $file->store('gallery', 'public'),
                'uploader_name' => 'Admin',
                'status' => 'approved',
            ]);
            $count++;
        }

        return back()->with('success', "{$count} foto(s) subida(s) y aprobada(s) correctamente.");
    }

    /**
     * Descargar una foto individual.
     */
    public function download(GalleryPhoto $photo)
    {
        return Storage::disk('public')->download($photo->image_path);
    }
}
```

**What's passed to Inertia (`index`):**
- `photos` → Paginated `GalleryPhoto` models (with `image_url` accessor automatically serialized).
- `counts` → `['pending' => N, 'approved' => N, 'rejected' => N]`.

---

### 1.5 Storage Symlink Check

**Result of checking `public/storage`:**

```
Type: Directory, ReparsePoint
Target: C:\Users\Veronica\...\storage\app\public
IS_SYMLINK: YES
```

✅ `php artisan storage:link` **has been run.** The symlink exists and points correctly to `storage/app/public`.

### 1.6 `config/filesystems.php` — `public` disk

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
    'visibility' => 'public',
    'throw' => false,
    'report' => false,
],
```

`APP_URL` = `http://localhost:8000` (from `.env`).  
So `Storage::disk('public')->url('gallery/abc.jpg')` → `http://localhost:8000/storage/gallery/abc.jpg`.

✅ This URL pattern is reachable assuming the dev server is running on port 8000 and the symlink is in place.

---

## 2. Backend — Settings / Cover Photo

### 2.1 Migration: `create_wedding_settings_table`

**File:** `database/migrations/2026_07_12_200100_create_wedding_settings_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_settings', function (Blueprint $table) {
            $table->id();
            $table->string('cover_photo_path')->nullable();
            $table->text('our_story')->nullable();
            $table->dateTime('event_datetime');
            $table->string('venue_name');
            $table->string('venue_address');
            $table->decimal('venue_lat', 10, 7)->nullable();
            $table->decimal('venue_lng', 10, 7)->nullable();
            $table->text('dress_code_description')->nullable();
            $table->string('dress_code_image_path')->nullable();
            $table->date('rsvp_deadline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_settings');
    }
};
```

**Original image columns:**
- `cover_photo_path` (string, nullable)
- `dress_code_image_path` (string, nullable)

### 2.2 Expansion Migration

**File:** `database/migrations/2026_07_13_000000_expand_wedding_settings.php`

Adds: `how_we_met_photo_path`, `proposal_photo_path`, `ceremony_photo_path`, `celebration_photo_path`, plus dress code detail columns (`dress_code_women_dress`, `dress_code_women_shoes`, etc. — these are `string` columns used to store **file paths** for reference images, not text descriptions; descriptions are stored in `*_desc` columns).

---

### 2.3 Model: `WeddingSetting`

**File:** `app/Models/WeddingSetting.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class WeddingSetting extends Model
{
    protected $table = 'wedding_settings';

    protected $fillable = [
        'cover_photo_path',
        'our_story',
        'how_we_met_story',
        'how_we_met_photo_path',
        'proposal_story',
        'proposal_photo_path',
        'event_datetime',
        'venue_name',
        'venue_address',
        'venue_lat',
        'venue_lng',
        'ceremony_title',
        'ceremony_datetime',
        'ceremony_address',
        'ceremony_lat',
        'ceremony_lng',
        'ceremony_photo_path',
        'celebration_title',
        'celebration_datetime',
        'celebration_address',
        'celebration_lat',
        'celebration_lng',
        'celebration_photo_path',
        'dress_code_description',
        'dress_code_image_path',
        'dress_code_general',
        'dress_code_women_dress',
        'dress_code_women_dress_desc',
        'dress_code_women_shoes',
        'dress_code_women_shoes_desc',
        'dress_code_women_accessories',
        'dress_code_women_accessories_desc',
        'dress_code_women_other',
        'dress_code_women_other_desc',
        'dress_code_men_suit',
        'dress_code_men_suit_desc',
        'dress_code_men_shoes',
        'dress_code_men_shoes_desc',
        'dress_code_men_accessories',
        'dress_code_men_accessories_desc',
        'dress_code_men_other',
        'dress_code_men_other_desc',
        'rsvp_deadline',
    ];

    protected function casts(): array
    {
        return [
            'event_datetime' => 'datetime',
            'ceremony_datetime' => 'datetime',
            'celebration_datetime' => 'datetime',
            'rsvp_deadline' => 'date',
        ];
    }

    /**
     * Singleton: siempre devuelve la primera (y única) fila, creándola con defaults si no existe.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'event_datetime' => '2026-11-14 16:00:00',
            'venue_name' => 'Por definir',
            'venue_address' => 'Por definir',
        ]);
    }

    /**
     * Accessor: URL pública completa de la foto de portada.
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo_path
            ? Storage::disk('public')->url($this->cover_photo_path)
            : null;
    }

    /**
     * Accessor: URL pública de la imagen de código de vestimenta.
     */
    public function getDressCodeImageUrlAttribute(): ?string
    {
        return $this->dress_code_image_path
            ? Storage::disk('public')->url($this->dress_code_image_path)
            : null;
    }

    // ── Accessors genéricos para nuevas fotos ──

    public function getHowWeMetPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->how_we_met_photo_path); }

    public function getProposalPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->proposal_photo_path); }

    public function getCeremonyPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->ceremony_photo_path); }

    public function getCelebrationPhotoUrlAttribute(): ?string
    { return $this->urlOrNull($this->celebration_photo_path); }

    public function getDressCodeWomenDressUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_dress); }

    public function getDressCodeWomenShoesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_shoes); }

    public function getDressCodeWomenAccessoriesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_accessories); }

    public function getDressCodeWomenOtherUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_women_other); }

    public function getDressCodeMenSuitUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_suit); }

    public function getDressCodeMenShoesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_shoes); }

    public function getDressCodeMenAccessoriesUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_accessories); }

    public function getDressCodeMenOtherUrlAttribute(): ?string
    { return $this->urlOrNull($this->dress_code_men_other); }

    private function urlOrNull(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
```

**Accessors available:**
- `cover_photo_url` → from `cover_photo_path`
- `dress_code_image_url` → from `dress_code_image_path`
- `how_we_met_photo_url` → from `how_we_met_photo_path`
- `proposal_photo_url` → from `proposal_photo_path`
- `ceremony_photo_url` → from `ceremony_photo_path`
- `celebration_photo_url` → from `celebration_photo_path`
- `dress_code_women_dress_url`, `dress_code_women_shoes_url`, etc. → from the corresponding `_desc` columns (note: the accessor reads from the file-path column, e.g. `dress_code_women_dress`, not the `*_desc` column)

All use `Storage::disk('public')->url(...)` — same pattern as Gallery.

---

### 2.4 `SettingController` (`edit` and `update`)

**File:** `app/Http/Controllers/Admin/SettingController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWeddingSettingRequest;
use App\Models\WeddingSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Muestra el formulario de edición de configuración del sitio.
     */
    public function edit()
    {
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => WeddingSetting::current(),
        ]);
    }

    /**
     * Guarda los cambios de configuración.
     */
    public function update(UpdateWeddingSettingRequest $request)
    {
        $settings = WeddingSetting::current();

        // Campos de texto (sin archivos)
        $excludedFiles = [
            'how_we_met_photo', 'proposal_photo',
            'ceremony_photo', 'celebration_photo',
            'dress_code_women_dress', 'dress_code_women_shoes',
            'dress_code_women_accessories', 'dress_code_women_other',
            'dress_code_men_suit', 'dress_code_men_shoes',
            'dress_code_men_accessories', 'dress_code_men_other',
        ];
        $data = $request->safe()->except($excludedFiles);

        // Mapa de archivos: [campo_request => columna_bd]
        $fileFields = [
            'how_we_met_photo' => 'how_we_met_photo_path',
            'proposal_photo' => 'proposal_photo_path',
            'ceremony_photo' => 'ceremony_photo_path',
            'celebration_photo' => 'celebration_photo_path',
            'dress_code_women_dress' => 'dress_code_women_dress',
            'dress_code_women_shoes' => 'dress_code_women_shoes',
            'dress_code_women_accessories' => 'dress_code_women_accessories',
            'dress_code_women_other' => 'dress_code_women_other',
            'dress_code_men_suit' => 'dress_code_men_suit',
            'dress_code_men_shoes' => 'dress_code_men_shoes',
            'dress_code_men_accessories' => 'dress_code_men_accessories',
            'dress_code_men_other' => 'dress_code_men_other',
        ];

        foreach ($fileFields as $inputName => $dbColumn) {
            if ($request->hasFile($inputName)) {
                // Eliminar archivo anterior si existe
                if ($settings->{$dbColumn}) {
                    Storage::disk('public')->delete($settings->{$dbColumn});
                }
                $data[$dbColumn] = $request->file($inputName)->store('settings', 'public');
            }
        }

        $settings->update($data);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
```

**🔴 CRITICAL FINDING: `cover_photo` and `dress_code_image` are NOT handled.**

The `$fileFields` array does **not** include:
- `'cover_photo' => 'cover_photo_path'`
- `'dress_code_image' => 'dress_code_image_path'`

Nor does the `UpdateWeddingSettingRequest` validate them (see below). This means:
- The settings page has **no way to upload a cover photo** or a dress code image.
- Even if a frontend field were added for them, the controller would silently ignore the file.

**Storage details for Settings files (when they do work):**
- Disk: `'public'`
- Directory: `'settings'` (→ `storage/app/public/settings/`)
- DB column gets the relative path (e.g., `settings/abc123.jpg`).
- Old file is deleted before saving new one.

---

### 2.5 `UpdateWeddingSettingRequest` Validation

**File:** `app\Http\Requests\UpdateWeddingSettingRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeddingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja con el middleware admin en la ruta
    }

    public function rules(): array
    {
        return [
            // Nuestra Historia
            'how_we_met_story' => ['nullable', 'string'],
            'how_we_met_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'proposal_story' => ['nullable', 'string'],
            'proposal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Cuándo y Dónde
            'event_datetime' => ['required', 'date'],

            // Ceremonia
            'ceremony_title' => ['nullable', 'string', 'max:255'],
            'ceremony_datetime' => ['nullable', 'date'],
            'ceremony_address' => ['nullable', 'string', 'max:500'],
            'ceremony_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'ceremony_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'ceremony_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Celebración
            'celebration_title' => ['nullable', 'string', 'max:255'],
            'celebration_datetime' => ['nullable', 'date'],
            'celebration_address' => ['nullable', 'string', 'max:500'],
            'celebration_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'celebration_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'celebration_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Código de vestimenta
            'dress_code_general' => ['nullable', 'string'],
            'dress_code_women_dress' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_dress_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_women_shoes' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_shoes_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_women_accessories' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_accessories_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_women_other' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_other_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_suit' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_suit_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_shoes' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_shoes_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_accessories' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_accessories_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_other' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_other_desc' => ['nullable', 'string', 'max:255'],

            // General
            'rsvp_deadline' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_datetime.required' => 'La fecha y hora del evento es obligatoria.',
        ];
    }
}
```

🔴 **`cover_photo` and `dress_code_image` are NOT validated** — confirming they are completely absent from the update flow.

---

### 2.6 `PageController@home` — Public Landing Page

**File:** `app/Http/Controllers/PageController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\WeddingSetting;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Landing page principal de la boda.
     * Devuelve todos los datos necesarios para las secciones de la página.
     */
    public function home()
    {
        return Inertia::render('Home', [
            'settings' => WeddingSetting::current(),
            'faqs' => Faq::published()->get(),
            'galleryPhotos' => GalleryPhoto::approved()->latest()->get(),
        ]);
    }
}
```

**Props passed:**
- `settings` → The `WeddingSetting` model (with all `*_url` accessors auto-serialized, including `cover_photo_url` and `dress_code_image_url`).
- `galleryPhotos` → Approved `GalleryPhoto` models (with `image_url` accessor auto-serialized).
- `faqs` → Published FAQ models.

---

## 3. Frontend — Gallery Admin Upload

### 3.1 Admin Gallery Page

**File:** `resources/js/Pages/Admin/Gallery/Index.vue`

```vue
<script setup>
import { ref } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DialogModal from '@/Components/DialogModal.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { PhotoIcon, CheckCircleIcon, XCircleIcon, TrashIcon, ArrowDownTrayIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    photos: Object,
    counts: Object,
});

const page = usePage();
const currentTab = ref(page.props.ziggy?.query?.status ?? 'pending');

function setTab(status) {
    currentTab.value = status;
    router.get(route('admin.gallery.index'), { status: status || undefined }, { preserveState: true, replace: true });
}

// ── Actions ──
const approveForm = useForm({});
function approve(photo) { approveForm.patch(route('admin.gallery.approve', photo.id), { preserveScroll: true }); }

const rejectForm = useForm({});
function reject(photo) { rejectForm.patch(route('admin.gallery.reject', photo.id), { preserveScroll: true }); }

const deletePhoto = ref(null);
const deleteForm = useForm({});
function confirmDelete(photo) { deletePhoto.value = photo; }
function doDelete() {
    if (!deletePhoto.value) return;
    deleteForm.delete(route('admin.gallery.destroy', deletePhoto.value.id), {
        preserveScroll: true,
        onSuccess: () => { deletePhoto.value = null; },
    });
}

// ── Lightbox ──
const lightboxImage = ref(null);
function openLightbox(url) { lightboxImage.value = url; }
function closeLightbox() { lightboxImage.value = null; }

// ── Downloads ──
function downloadPhoto(photo) {
    window.open(route('admin.gallery.download', photo.id), '_blank');
}

const isDownloadingAll = ref(false);
async function downloadAll() {
    if (!props.photos?.data?.length) return;
    isDownloadingAll.value = true;
    try {
        for (const photo of props.photos.data) {
            downloadPhoto(photo);
            await new Promise(r => setTimeout(r, 400));
        }
    } finally {
        isDownloadingAll.value = false;
    }
}

// ── Upload ──
const showUploadModal = ref(false);
const uploadFiles = ref([]);
const uploadForm = useForm({ images: [] });

function onFilesSelected(e) {
    uploadFiles.value = Array.from(e.target.files);
}

function doUpload() {
    if (!uploadFiles.value.length) return;
    const formData = new FormData();
    uploadFiles.value.forEach(f => formData.append('images[]', f));
    uploadForm.post(route('admin.gallery.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            showUploadModal.value = false;
            uploadFiles.value = [];
        },
    });
}
</script>

<template>
    <AppLayout title="Galería">
        <template #header>
            <h2 class="font-slab text-xl text-cuero leading-tight">Galería — Moderación</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Tabs + Actions -->
            <div class="flex gap-2 mb-8 flex-wrap items-center justify-between">
                <div class="flex gap-2 flex-wrap items-center">
                <button @click="setTab('pending')"
                    :class="currentTab === 'pending' ? 'bg-dorado text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Pendientes ({{ counts?.pending ?? 0 }})
                </button>
                <button @click="setTab('approved')"
                    :class="currentTab === 'approved' ? 'bg-olivo text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Aprobadas ({{ counts?.approved ?? 0 }})
                </button>
                <button @click="setTab('rejected')"
                    :class="currentTab === 'rejected' ? 'bg-red-500 text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Rechazadas ({{ counts?.rejected ?? 0 }})
                </button>
                </div>
                <div class="flex gap-2">
                    <button @click="showUploadModal = true"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all bg-dorado hover:bg-dorado-dark text-white">
                        <ArrowUpTrayIcon class="w-4 h-4" />
                        Subir fotos
                    </button>
                    <button v-if="photos?.data?.length > 0" @click="downloadAll" :disabled="isDownloadingAll"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all bg-mezclilla hover:bg-cuero text-white disabled:opacity-50"
                        title="Descargar todas las fotos visibles">
                        <ArrowDownTrayIcon class="w-4 h-4" />
                        {{ isDownloadingAll ? 'Descargando...' : 'Descargar todas' }}
                    </button>
                </div>
            </div>

            <!-- Photo Grid -->
            <div v-if="photos?.data?.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
                <div v-for="photo in photos.data" :key="photo.id"
                    class="relative aspect-square rounded-2xl overflow-hidden group shadow-sm hover:shadow-lg transition-all duration-300">
                    <img :src="photo.image_url" alt="Foto" class="w-full h-full object-cover" @click="openLightbox(photo.image_url)" />

                    <!-- Status badge -->
                    <div class="absolute top-2 right-2">
                        <StatusBadge :status="photo.status" variant="gallery" />
                    </div>

                    <!-- Overlay con acciones -->
                    <div class="absolute inset-0 bg-cuero/0 group-hover:bg-cuero/40 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <button @click.stop="downloadPhoto(photo)"
                            class="w-9 h-9 bg-white/90 hover:bg-white text-cuero rounded-full flex items-center justify-center transition-colors shadow-lg" title="Descargar">
                            <ArrowDownTrayIcon class="w-4 h-4" />
                        </button>
                        <button v-if="photo.status !== 'approved'" @click.stop="approve(photo)"
                            class="w-9 h-9 bg-olivo hover:bg-olivo-dark text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Aprobar">
                            <CheckCircleIcon class="w-5 h-5" />
                        </button>
                        <button v-if="photo.status !== 'rejected'" @click.stop="reject(photo)"
                            class="w-9 h-9 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Rechazar">
                            <XCircleIcon class="w-5 h-5" />
                        </button>
                        <ConfirmDeleteModal :message="'¿Eliminar esta foto permanentemente? No se puede deshacer.'" @confirm="doDelete">
                            <template #default="{ open: openDel }">
                                <button @click.stop="confirmDelete(photo); openDel()"
                                    class="w-9 h-9 bg-cuero hover:bg-cuero-dark text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Eliminar">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </template>
                        </ConfirmDeleteModal>
                    </div>

                    <!-- Uploader name -->
                    <div v-if="photo.uploader_name" class="absolute bottom-2 left-2">
                        <span class="bg-cuero/70 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-lg">
                            {{ photo.uploader_name }}
                        </span>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="PhotoIcon"
                :title="currentTab === 'pending' ? 'No hay fotos pendientes de revisión 🎉' : currentTab === 'approved' ? 'No hay fotos aprobadas' : 'No hay fotos rechazadas'"
                :description="currentTab === 'pending' ? '¡Todo en orden! Las fotos nuevas aparecerán aquí.' : ''" />

            <!-- Pagination -->
            <div v-if="photos?.links?.length > 3" class="mt-8 flex items-center justify-between">
                <span class="text-xs text-cuero/50">{{ photos.from }}–{{ photos.to }} de {{ photos.total }}</span>
                <div class="flex gap-1">
                    <a v-for="link in photos.links" :key="link.label"
                        :href="link.url ?? '#'" v-html="link.label"
                        :class="['px-3 py-1.5 rounded-lg text-sm transition-colors', link.active ? 'bg-cuero text-white' : link.url ? 'text-cuero/60 hover:bg-arena' : 'text-cuero/20 cursor-default']"></a>
                </div>
            </div>

            <!-- Lightbox -->
            <Teleport to="body">
                <div v-if="lightboxImage" @click="closeLightbox"
                    class="fixed inset-0 z-[100] bg-cuero/95 backdrop-blur-sm flex items-center justify-center p-4 cursor-pointer">
                    <button @click="closeLightbox" class="absolute top-6 right-6 text-white/60 hover:text-white transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <img :src="lightboxImage" alt="Foto ampliada" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" @click.stop />
                </div>
            </Teleport>

            <!-- Upload Modal -->
            <DialogModal :show="showUploadModal" @close="showUploadModal = false; uploadFiles = [];">
                <template #title>Subir Fotos a la Galería</template>
                <template #content>
                    <div class="space-y-4">
                        <p class="text-sm text-cuero/60">Las fotos subidas desde aquí se aprobarán automáticamente. Puedes seleccionar múltiples archivos.</p>
                        <div class="border-2 border-dashed border-cuero/20 rounded-2xl p-8 text-center hover:border-dorado/40 transition-colors">
                            <PhotoIcon class="w-10 h-10 text-cuero/20 mx-auto mb-3" />
                            <label class="cursor-pointer inline-flex items-center gap-2 bg-arena hover:bg-arena-dark text-cuero px-5 py-2.5 rounded-xl text-sm font-medium border border-cuero/20 transition-colors">
                                <ArrowUpTrayIcon class="w-4 h-4" />
                                Seleccionar fotos
                                <input type="file" accept="image/*" multiple class="hidden" @change="onFilesSelected" />
                            </label>
                            <p v-if="uploadFiles.length > 0" class="text-cuero text-sm mt-3 font-medium">{{ uploadFiles.length }} foto(s) seleccionada(s)</p>
                            <div v-if="uploadFiles.length > 0" class="flex flex-wrap gap-2 mt-3 justify-center">
                                <span v-for="(f, i) in uploadFiles" :key="i" class="text-xs bg-arena px-2 py-1 rounded-lg text-cuero/70 truncate max-w-[150px]">{{ f.name }}</span>
                            </div>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showUploadModal = false; uploadFiles = [];">Cancelar</SecondaryButton>
                    <PrimaryButton @click="doUpload" :disabled="!uploadFiles.length || uploadForm.processing" class="ms-3" :class="{ 'opacity-50': !uploadFiles.length || uploadForm.processing }">
                        {{ uploadForm.processing ? 'Subiendo...' : `Subir ${uploadFiles.length ? uploadFiles.length : ''} foto(s)` }}
                    </PrimaryButton>
                </template>
            </DialogModal>
        </div>
    </div>
    </AppLayout>
</template>
```

**How the upload form works:**

1. **File input handling:** `@change="onFilesSelected"` → `uploadFiles.value = Array.from(e.target.files)` — stores files in local reactive state.
2. **Form submission:** `doUpload()` manually constructs a `FormData` and appends files with key `images[]`. Then calls `uploadForm.post(route('admin.gallery.store'), { forceFormData: true, ... })`.
   - ✅ Uses `forceFormData: true` (required for file uploads via Inertia).
   - ✅ Manually builds `FormData` and appends files before calling `.post()`.
3. **Post-upload:** On success: closes modal, clears `uploadFiles`. The `preserveScroll: true` ensures the page does a full Inertia reload, which will re-fetch `props.photos` from the server — new photos **will appear** in the list automatically.
4. **Image `src` binding:** `<img :src="photo.image_url" ...>` — uses the `image_url` accessor. This produces a URL like `http://localhost:8000/storage/gallery/<hash>.jpg`.
5. **No hardcoded path prefixes** — purely relies on the accessor.

**Status filter (tabs):** ✅ Implemented.
- Three tabs: `pending`, `approved`, `rejected`.
- `currentTab` initialized from `ziggy.query.status` (URL query param).
- `setTab(status)` calls `router.get(route('admin.gallery.index'), { status }, ...)` which triggers a server-side filter via `->when(request()->query('status'), ...)` in the controller.

---

## 4. Frontend — Settings Page

### 4.1 Admin Settings Edit Page

**File:** `resources/js/Pages/Admin/Settings/Edit.vue`

```vue
<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import { PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ settings: Object });

const form = useForm({
    _method: 'PUT',
    // Nuestra Historia
    how_we_met_story: props.settings?.how_we_met_story ?? '',
    how_we_met_photo: null,
    proposal_story: props.settings?.proposal_story ?? '',
    proposal_photo: null,
    // Cuándo y Dónde
    event_datetime: props.settings?.event_datetime ?? '',
    ceremony_title: props.settings?.ceremony_title ?? '',
    ceremony_datetime: props.settings?.ceremony_datetime ?? '',
    ceremony_address: props.settings?.ceremony_address ?? '',
    ceremony_lat: props.settings?.ceremony_lat ?? '',
    ceremony_lng: props.settings?.ceremony_lng ?? '',
    ceremony_photo: null,
    celebration_title: props.settings?.celebration_title ?? '',
    celebration_datetime: props.settings?.celebration_datetime ?? '',
    celebration_address: props.settings?.celebration_address ?? '',
    celebration_lat: props.settings?.celebration_lat ?? '',
    celebration_lng: props.settings?.celebration_lng ?? '',
    celebration_photo: null,
    // Código de Vestimenta
    dress_code_general: props.settings?.dress_code_general ?? '',
    dress_code_women_dress: null,
    dress_code_women_dress_desc: props.settings?.dress_code_women_dress_desc ?? '',
    dress_code_women_shoes: null,
    dress_code_women_shoes_desc: props.settings?.dress_code_women_shoes_desc ?? '',
    dress_code_women_accessories: null,
    dress_code_women_accessories_desc: props.settings?.dress_code_women_accessories_desc ?? '',
    dress_code_women_other: null,
    dress_code_women_other_desc: props.settings?.dress_code_women_other_desc ?? '',
    dress_code_men_suit: null,
    dress_code_men_suit_desc: props.settings?.dress_code_men_suit_desc ?? '',
    dress_code_men_shoes: null,
    dress_code_men_shoes_desc: props.settings?.dress_code_men_shoes_desc ?? '',
    dress_code_men_accessories: null,
    dress_code_men_accessories_desc: props.settings?.dress_code_men_accessories_desc ?? '',
    dress_code_men_other: null,
    dress_code_men_other_desc: props.settings?.dress_code_men_other_desc ?? '',
    // General
    rsvp_deadline: props.settings?.rsvp_deadline ?? '',
});

// Previews
const howWeMetPreview = ref(props.settings?.how_we_met_photo_url ?? null);
const proposalPreview = ref(props.settings?.proposal_photo_url ?? null);
const ceremonyPreview = ref(props.settings?.ceremony_photo_url ?? null);
const celebrationPreview = ref(props.settings?.celebration_photo_url ?? null);

const womenDressPreview = ref(props.settings?.dress_code_women_dress_url ?? null);
const womenShoesPreview = ref(props.settings?.dress_code_women_shoes_url ?? null);
const womenAccessoriesPreview = ref(props.settings?.dress_code_women_accessories_url ?? null);
const womenOtherPreview = ref(props.settings?.dress_code_women_other_url ?? null);

const menSuitPreview = ref(props.settings?.dress_code_men_suit_url ?? null);
const menShoesPreview = ref(props.settings?.dress_code_men_shoes_url ?? null);
const menAccessoriesPreview = ref(props.settings?.dress_code_men_accessories_url ?? null);
const menOtherPreview = ref(props.settings?.dress_code_men_other_url ?? null);

function setPreview(e, refKey) {
    const file = e.target.files[0];
    if (!file) return;
    const url = URL.createObjectURL(file);
    const previewMap = {
        how_we_met_photo: howWeMetPreview,
        proposal_photo: proposalPreview,
        ceremony_photo: ceremonyPreview,
        celebration_photo: celebrationPreview,
        dress_code_women_dress: womenDressPreview,
        dress_code_women_shoes: womenShoesPreview,
        dress_code_women_accessories: womenAccessoriesPreview,
        dress_code_women_other: womenOtherPreview,
        dress_code_men_suit: menSuitPreview,
        dress_code_men_shoes: menShoesPreview,
        dress_code_men_accessories: menAccessoriesPreview,
        dress_code_men_other: menOtherPreview,
    };
    form[refKey] = file;
    if (previewMap[refKey]) previewMap[refKey].value = url;
}

function submit() {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<!-- Template with sections for: Nuestra Historia, Cuándo y Dónde,
     Código de Vestimenta, RSVP -->
<!-- (Full template above — each photo section follows the same pattern:
     an <img :src="*Preview" /> for current/preview, and a hidden
     <input type="file" @change="setPreview($event, '<key>')" />) -->
```

**How the form is built:**
- Uses `useForm()` with `_method: 'PUT'` (for Laravel method spoofing when posting).
- All file fields are initialized to `null`.
- **🔴 Does NOT include `cover_photo` or `dress_code_image` fields.**
- Preview refs are initialized from server props (e.g., `props.settings?.how_we_met_photo_url`), which come from the accessors.
- `setPreview(e, refKey)` creates a local blob URL for instant preview AND sets `form[refKey] = file`.
- `submit()` calls `form.post(route('admin.settings.update'), { preserveScroll: true, forceFormData: true })`.
  - ✅ Uses `forceFormData: true`.

**Preview display behavior:**
- On page load: preview refs are set from `props.settings.*_photo_url` (server URLs → correct).
- After selecting a new file: preview is replaced with a local `blob:` URL (via `URL.createObjectURL`).
- After save: `form.post` with `preserveScroll: true` triggers an Inertia response, which re-renders `props.settings` with updated `*_photo_url` values. The preview refs are **not** re-initialized from the new props because they are `ref()` values initialized once in `<script setup>`. **This means after save, the preview will still show the old `blob:` URL** until the user does a full page reload or the component is re-mounted. ⚠️ **This is a potential UX issue but not a file-saving bug.**

---

### 4.2 Landing Page — Hero / Cover Photo Display

**File:** `resources/js/Pages/Home.vue` (hero section excerpt)

```vue
<section id="hero" class="relative min-h-screen flex items-center justify-center overflow-hidden">
    <!-- Background image with overlay -->
    <div class="absolute inset-0 z-0">
        <img
            v-if="settings?.cover_photo_url"
            :src="settings.cover_photo_url"
            alt="Portada"
            class="w-full h-full object-cover"
        />
        <div v-else class="w-full h-full bg-gradient-to-br from-cuero via-olivo-dark to-mezclilla"></div>
        <!-- Overlays -->
        <div class="absolute inset-0 bg-gradient-to-b from-cuero/60 via-cuero/30 to-cuero/70"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-arena/90 via-transparent to-transparent"></div>
    </div>
    <!-- ...hero content... -->
</section>
```

**How `cover_photo_url` is used:**
- `<img v-if="settings?.cover_photo_url" :src="settings.cover_photo_url" ... />`
- Falls back to a CSS gradient if `cover_photo_url` is null/undefined.
- Bound directly to the accessor value from the server — no manual path construction.

### 4.3 Dress Code Image on Landing Page

The dress code section in `Home.vue` uses **hardcoded emoji-based placeholders** for the moodboard (e.g., 🤠, 👔, 👢). The `dress_code_image_url` accessor exists on the model but is **not used** in the `Home.vue` template.

---

## 5. General Checks

### 5.1 `APP_URL` and URL Resolution

```
APP_URL=http://localhost:8000
```

With `config/filesystems.php`:

```php
'public' => [
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
    // → 'http://localhost:8000/storage'
],
```

**Example constructed URLs from actual code:**

| Source | Code | Example Result |
|--------|------|----------------|
| Gallery photo | `Storage::disk('public')->url('gallery/abc123.jpg')` | `http://localhost:8000/storage/gallery/abc123.jpg` |
| Settings cover photo | `Storage::disk('public')->url('settings/xyz789.jpg')` | `http://localhost:8000/storage/settings/xyz789.jpg` |

Both are reachable assuming:
- Laravel dev server is running on port 8000.
- `public/storage` symlink exists (✅ confirmed).
- The file actually exists in `storage/app/public/gallery/` or `storage/app/public/settings/`.

### 5.2 Inconsistencies Between Gallery and Settings Storage

| Aspect | Gallery | Settings |
|--------|---------|----------|
| **Disk** | `'public'` | `'public'` |
| **Subdirectory** | `'gallery'` | `'settings'` |
| **URL accessor** | `Storage::disk('public')->url(...)` | `Storage::disk('public')->url(...)` |
| **Old file deletion** | Only on `destroy()` (manual delete) | On update, old file deleted before saving new one |
| **Upload via frontend** | ✅ Full flow implemented | ⚠️ Partial — `cover_photo` and `dress_code_image` missing |
| **Validation** | `StoreGalleryPhotoRequest` (image required) | `UpdateWeddingSettingRequest` (no `cover_photo`/`dress_code_image` rules) |

---

## 6. Summary of Issues Found

### 🔴 Critical: Cover Photo and Dress Code Image Cannot Be Uploaded

**The Settings page has no way to upload `cover_photo` or `dress_code_image`.**

The chain is broken at three points:
1. **`UpdateWeddingSettingRequest`** — no validation rules for `cover_photo` or `dress_code_image`.
2. **`SettingController::update()`** — `$fileFields` array does not map `cover_photo` → `cover_photo_path` or `dress_code_image` → `dress_code_image_path`.
3. **`Settings/Edit.vue`** — the `useForm()` data has no `cover_photo` or `dress_code_image` fields, and there are no file inputs for them in the template.

The `WeddingSetting` model **does** have the `cover_photo_url` and `dress_code_image_url` accessors, and the `Home.vue` template **does** use `settings.cover_photo_url` in the hero section. So the display side is ready — but there is no upload path to populate the underlying `cover_photo_path` / `dress_code_image_path` columns.

### 🟡 Minor: Settings Preview URL Persistence After Save

In `Settings/Edit.vue`, the preview refs are initialized once from props and then overwritten with `blob:` URLs when a new file is selected. After a successful save, Inertia re-renders with updated props, but the preview refs retain the `blob:` URLs. This means the preview won't update to show the newly saved server URL until a full page reload. This is a UX concern, not a data loss issue.

### ✅ Working Correctly

- `public/storage` symlink exists and points to the correct target.
- Gallery public upload (from `Home.vue`) stores files to `storage/app/public/gallery/` using disk `'public'`.
- Gallery admin upload stores files to the same location, auto-approved.
- Admin gallery page renders `photo.image_url` correctly, with status filter tabs working.
- Gallery approve/reject/delete actions work correctly.
- The `GalleryPhoto.image_url` accessor uses `Storage::disk('public')->url()` — consistent with all other accessors.
- All `WeddingSetting` photo accessors use `Storage::disk('public')->url()` — consistent pattern.
- `PageController@home` passes `settings` (with all accessors) and `galleryPhotos` to the Home page.
- `Home.vue` uses `settings.cover_photo_url` correctly in the hero and `photo.image_url` in the gallery grid.
- Settings form uses `useForm` + `forceFormData: true` for file uploads.
