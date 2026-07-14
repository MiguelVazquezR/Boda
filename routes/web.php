<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\GuestController as AdminGuestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RsvpController;
use Illuminate\Support\Facades\Route;

// ─── Rutas públicas ───────────────────────────────────────────────

Route::get('/', [PageController::class, 'home'])->name('home');

// RSVP (confirmación de asistencia) — público, sin login
Route::get('/rsvp/buscar', [RsvpController::class, 'search'])->name('rsvp.search');
Route::post('/rsvp/{guest}/confirmar', [RsvpController::class, 'confirm'])
    ->name('rsvp.confirm')
    ->middleware('throttle:10,1'); // rate limiting: 10 intentos por minuto

// Galería — subida pública de fotos
Route::post('/galeria', [GalleryController::class, 'store'])
    ->name('gallery.store')
    ->middleware('throttle:5,1'); // rate limiting: 5 subidas por minuto

// ─── Rutas autenticadas (Jetstream) ───────────────────────────────

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Dashboard principal (requiere ser admin — muestra KPIs y actividad)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('admin')
        ->name('dashboard');

    // ─── Panel Admin (solo admin) ─────────────────────────────────
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

        // Configuración del sitio
        Route::get('/configuracion', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracion', [SettingController::class, 'update'])->name('settings.update');

        // Invitados
        Route::get('/invitados', [AdminGuestController::class, 'index'])->name('guests.index');
        Route::post('/invitados', [AdminGuestController::class, 'store'])->name('guests.store');
        Route::post('/invitados/importar', [AdminGuestController::class, 'import'])->name('guests.import');
        Route::put('/invitados/{guest}', [AdminGuestController::class, 'update'])->name('guests.update');
        Route::delete('/invitados/{guest}', [AdminGuestController::class, 'destroy'])->name('guests.destroy');

        // FAQs
        Route::get('/faqs', [AdminFaqController::class, 'index'])->name('faqs.index');
        Route::post('/faqs', [AdminFaqController::class, 'store'])->name('faqs.store');
        Route::put('/faqs/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
        Route::delete('/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');

        // Galería (moderación)
        Route::get('/galeria', [AdminGalleryController::class, 'index'])->name('gallery.index');
        Route::post('/galeria/upload', [AdminGalleryController::class, 'store'])->name('gallery.store');
        Route::get('/galeria/{photo}/descargar', [AdminGalleryController::class, 'download'])->name('gallery.download');
        Route::patch('/galeria/{photo}/aprobar', [AdminGalleryController::class, 'approve'])->name('gallery.approve');
        Route::patch('/galeria/{photo}/rechazar', [AdminGalleryController::class, 'reject'])->name('gallery.reject');
        Route::delete('/galeria/{photo}', [AdminGalleryController::class, 'destroy'])->name('gallery.destroy');
    });

});
