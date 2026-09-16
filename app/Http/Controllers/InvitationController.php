<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\WeddingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Cookie as CookieInstance;

/**
 * Invitación digital: la puerta de apertura que se comparte con cada invitado.
 *
 * El arte animado (música y transiciones) vive en Canva, porque esas animaciones
 * no se pueden exportar. Por eso aquí sólo se muestra un sobre elegante con el
 * nombre del invitado o de la pareja y, al abrir, se pasa al sitio de Canva
 * recordando en una cookie quién abrió el link para poder precargar después su
 * confirmación de asistencia.
 */
class InvitationController extends Controller
{
    /** Cookie que recuerda qué invitación abrió el visitante. */
    public const COOKIE = 'invitation_token';

    /** Vigencia: 180 días (desde que se envía la invitación hasta la boda). */
    private const COOKIE_MINUTES = 60 * 24 * 180;

    /**
     * Puerta de apertura: sobre elegante con el nombre del invitado o la pareja.
     */
    public function show(Invitation $invitation): Response
    {
        // Las respuestas de Inertia no exponen withCookie(): la cookie se encola
        // y el middleware AddQueuedCookiesToResponse la añade a la respuesta.
        Cookie::queue($this->remember($invitation));

        return Inertia::render('Invitacion', [
            'invitation' => $this->payload($invitation),
            'coupleNames' => WeddingSetting::coupleNames(),
            'eventDateTime' => WeddingSetting::current()->event_datetime,
        ]);
    }

    /**
     * Abre el sitio de Canva de la invitación (música y transiciones), dejando
     * registrado antes quién abrió el link.
     */
    public function open(Invitation $invitation): RedirectResponse
    {
        return redirect()
            ->away(WeddingSetting::current()->invitationArtworkUrl())
            ->withCookie($this->remember($invitation));
    }

    /**
     * Olvida la invitación recordada en la cookie («¿no eres tú?»): así el
     * invitado puede buscar su nombre a mano, por ejemplo si le reenviaron
     * el link de otra persona.
     */
    public function forget(): RedirectResponse
    {
        return redirect()
            ->route('rsvp.landing')
            ->withoutCookie(self::COOKIE);
    }

    /**
     * Datos mínimos que necesita la puerta de apertura.
     */
    private function payload(Invitation $invitation): array
    {
        return [
            'token' => $invitation->token,
            'display_name' => $invitation->display_name,
            'members' => $invitation->members()
                ->get(['id', 'full_name', 'rsvp_status'])
                ->map(fn (Guest $guest) => [
                    'id' => $guest->id,
                    'full_name' => $guest->full_name,
                    'rsvp_status' => $guest->rsvp_status,
                ])
                ->values(),
            'open_url' => route('invitation.open', $invitation->token),
            'rsvp_url' => route('invitation.rsvp', $invitation->token),
        ];
    }

    /**
     * Cookie (encriptada por Laravel) con el token de la invitación.
     */
    private function remember(Invitation $invitation): CookieInstance
    {
        return cookie(self::COOKIE, $invitation->token, self::COOKIE_MINUTES);
    }
}
