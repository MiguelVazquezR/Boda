<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\GuestController as AdminGuestController;
use App\Http\Controllers\Admin\GuestGroupController;
use App\Http\Controllers\Admin\InvitationController as AdminInvitationController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TableController as AdminTableController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\TableController;
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

// Encuentra tu mesa — consulta pública de la mesa asignada (sin login)
Route::get('/mesas/buscar', [TableController::class, 'search'])
    ->name('tables.search')
    ->middleware('throttle:30,1'); // rate limiting: 30 búsquedas por minuto

// ─── Invitación digital (pública, sin login) ──────────────────────
//  · /i/{token}            → puerta de apertura con el nombre de los invitados
//  · /i/{token}/abrir      → guarda la cookie del invitado y abre Canva (música + transiciones)
//  · /i/{token}/confirmar  → confirmación de asistencia con los nombres precargados
Route::get('/i/{invitation}', [InvitationController::class, 'show'])
    ->name('invitation.show')
    ->middleware('throttle:60,1');

Route::get('/i/{invitation}/abrir', [InvitationController::class, 'open'])
    ->name('invitation.open')
    ->middleware('throttle:60,1');

Route::get('/i/{invitation}/confirmar', [PageController::class, 'rsvp'])
    ->name('invitation.rsvp')
    ->middleware('throttle:60,1');

// Link fijo para el botón «Confirmar asistencia» dentro del sitio de Canva:
// recuerda al invitado por cookie y abre la confirmación con su nombre cargado.
Route::get('/rsvp', [PageController::class, 'rsvp'])
    ->name('rsvp.landing')
    ->middleware('throttle:60,1');

// «¿No eres tú?»: olvida la invitación recordada en la cookie.
Route::get('/rsvp/olvidar', [InvitationController::class, 'forget'])
    ->name('rsvp.forget')
    ->middleware('throttle:60,1');

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
        Route::get('/invitados/nuevo', [AdminGuestController::class, 'create'])->name('guests.create');
        Route::get('/invitados/{guest}/editar', [AdminGuestController::class, 'edit'])->name('guests.edit');
        Route::post('/invitados', [AdminGuestController::class, 'store'])->name('guests.store');
        Route::post('/invitados/importar', [AdminGuestController::class, 'import'])->name('guests.import');
        Route::put('/invitados/{guest}', [AdminGuestController::class, 'update'])->name('guests.update');
        Route::delete('/invitados/{guest}', [AdminGuestController::class, 'destroy'])->name('guests.destroy');

        // Grupos de invitados
        Route::post('/grupos', [GuestGroupController::class, 'store'])->name('groups.store');
        Route::put('/grupos/{group}', [GuestGroupController::class, 'update'])->name('groups.update');
        Route::delete('/grupos/{group}', [GuestGroupController::class, 'destroy'])->name('groups.destroy');

        // Invitaciones digitales (link por pareja o por persona sola)
        Route::post('/invitaciones', [AdminInvitationController::class, 'store'])->name('invitations.store');
        Route::post('/invitaciones/individuales', [AdminInvitationController::class, 'storeSingle'])->name('invitations.single');
        Route::put('/invitaciones/{invitation:id}', [AdminInvitationController::class, 'update'])->name('invitations.update');
        Route::post('/invitaciones/{invitation:id}/link', [AdminInvitationController::class, 'regenerateToken'])->name('invitations.token');
        Route::put('/invitaciones/{invitation:id}/enviada', [AdminInvitationController::class, 'markSent'])->name('invitations.sent');
        Route::delete('/invitaciones/{invitation:id}', [AdminInvitationController::class, 'destroy'])->name('invitations.destroy');

        // Mesas (gestión de mesas y asignación de invitados)
        Route::get('/mesas', [AdminTableController::class, 'index'])->name('tables.index');
        Route::post('/mesas', [AdminTableController::class, 'store'])->name('tables.store');
        Route::post('/mesas/asignar', [AdminTableController::class, 'assign'])->name('tables.assign');
        Route::put('/mesas/{guestTable}', [AdminTableController::class, 'update'])->name('tables.update');
        Route::delete('/mesas/{guestTable}', [AdminTableController::class, 'destroy'])->name('tables.destroy');

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

        // Itinerario / Tiempos
        Route::get('/tempos', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/tempos', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::post('/tempos/{item}/mover', [ScheduleController::class, 'move'])->name('schedule.move');
        Route::put('/tempos/{item}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('/tempos/{item}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    });

});
