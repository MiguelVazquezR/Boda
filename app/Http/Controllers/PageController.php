<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\GalleryPhoto;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\ScheduleItem;
use App\Models\WeddingSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    /**
     * Landing page principal de la boda.
     * Devuelve todos los datos necesarios para las secciones de la página.
     */
    public function home(Request $request)
    {
        return Inertia::render('Home', $this->props($request, focusRsvp: false));
    }

    /**
     * La misma landing, abriendo directamente la confirmación de asistencia.
     *
     * Se usa en dos casos: el link fijo /rsvp (el que se configura en el botón
     * «Confirmar asistencia» dentro de Canva) y el link directo de cada
     * invitación (/i/{token}/confirmar).
     */
    public function rsvp(Request $request)
    {
        return Inertia::render('Home', $this->props($request, focusRsvp: true));
    }

    /**
     * Props compartidos por las dos entradas a la landing.
     */
    private function props(Request $request, bool $focusRsvp): array
    {
        $invitation = $this->resolveInvitation($request);
        $settings = WeddingSetting::current();

        return [
            'settings' => $settings,
            'faqs' => Faq::published()->get(),
            'galleryPhotos' => GalleryPhoto::approved()->latest()->get(),
            'scheduleItems' => ScheduleItem::active()->ordered()->get(),
            // La sección "Encuentra tu mesa" se activa automáticamente cuando ya
            // existe al menos un invitado con mesa registrada en la base de datos.
            'tablesReady' => Guest::whereNotNull('table_group')
                ->where('table_group', '<>', '')
                ->exists(),
            // Fecha en la que se publican las mesas. Antes de esa fecha la sección
            // muestra un aviso («vuelve el …») en lugar del buscador, porque las
            // mesas se distribuyen después de revisar las confirmaciones.
            'tablesRevealDate' => $settings->tables_reveal_date?->format('Y-m-d\TH:i:sP'),
            // Invitación reconocida: por link directo o por la cookie que se guardó
            // cuando el invitado abrió su invitación y pasó a Canva.
            'invitation' => $invitation ? $this->invitationPayload($invitation) : null,
            'focusRsvp' => $focusRsvp,
            // Link de la mesa de regalos (lista de sugerencias). Editable desde el
            // panel; si está vacío se usa config/wedding.php.
            'giftRegistryUrl' => $settings->giftRegistryUrl(),
            // Cuenta bancaria para regalos (segunda opción, opcional).
            'giftBank' => $settings->giftBankDetails(),
        ];
    }

    /**
     * Resuelve la invitación del visitante: primero por el link directo
     * (/i/{token}/confirmar) y, si no, por la cookie de la invitación abierta.
     *
     * El parámetro de ruta llega como string cuando la ruta no lo tipa como
     * modelo (PageController@rsvp recibe un Request), así que se resuelve aquí
     * por su token.
     */
    private function resolveInvitation(Request $request): ?Invitation
    {
        $invitation = $request->route('invitation');

        if ($invitation instanceof Invitation) {
            return $invitation;
        }

        if (is_string($invitation) && $invitation !== '') {
            return Invitation::where('token', $invitation)->first();
        }

        $token = $request->cookie(InvitationController::COOKIE);

        return $token ? Invitation::where('token', $token)->first() : null;
    }

    /**
     * Invitados de la invitación con su estado de confirmación.
     */
    private function invitationPayload(Invitation $invitation): array
    {
        return [
            'token' => $invitation->token,
            'display_name' => $invitation->display_name,
            'members' => $invitation->members()
                ->get(['id', 'full_name', 'rsvp_status', 'rsvp_message'])
                ->map(fn (Guest $guest) => [
                    'id' => $guest->id,
                    'full_name' => $guest->full_name,
                    'rsvp_status' => $guest->rsvp_status,
                    'rsvp_message' => $guest->rsvp_message,
                ])
                ->values(),
        ];
    }
}

