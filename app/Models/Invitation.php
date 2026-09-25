<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invitation extends Model
{
    /**
     * Máximo de invitados por invitación: la invitación se manda a una pareja
     * (2 personas) o a una persona sola cuando no tiene pareja.
     */
    public const MAX_MEMBERS = 2;

    /** Alfabeto del token público: sin caracteres ambiguos (i, l, o, 0, 1). */
    private const TOKEN_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    protected $fillable = [
        'token',
        'display_name',
        'sent_at',
    ];

    /**
     * El link público y el texto de WhatsApp se incluyen siempre en la
     * serialización (panel de invitados y modal de detalle los usan sin
     * cálculos extra en el frontend).
     */
    protected $appends = [
        'public_url',
        'share_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invitation $invitation) {
            $invitation->token ??= static::generateToken();
        });
    }

    /**
     * La URL pública de la invitación usa el token, nunca el id.
     */
    public function getRouteKeyName(): string
    {
        return 'token';
    }

    /**
     * Invitados a los que va dirigida esta invitación (1 o 2 personas).
     */
    public function members(): HasMany
    {
        return $this->hasMany(Guest::class)->orderBy('first_name');
    }

    /**
     * Genera un token único, corto y fácil de dictar o compartir por WhatsApp.
     */
    public static function generateToken(int $length = 10): string
    {
        $alphabet = self::TOKEN_ALPHABET;
        $max = strlen($alphabet) - 1;

        do {
            $token = '';

            for ($i = 0; $i < $length; $i++) {
                $token .= $alphabet[random_int(0, $max)];
            }
        } while (static::where('token', $token)->exists());

        return $token;
    }

    /**
     * Nombre sugerido para mostrar en la invitación a partir de sus miembros,
     * con su nombre completo: "Ana Pérez & Luis García" para una pareja, o el
     * nombre de la persona si va sola.
     */
    public static function suggestDisplayName(Collection $members): string
    {
        $names = $members
            ->map(fn (Guest $guest) => trim((string) ($guest->full_name ?: $guest->first_name)))
            ->filter()
            ->values();

        return $names->isEmpty() ? 'Invitado' : $names->implode(' & ');
    }

    /**
     * Link que se comparte con los invitados (la puerta de apertura).
     */
    public function publicUrl(): string
    {
        return route('invitation.show', $this->token);
    }

    /**
     * Accessor del link público: /i/{token}
     */
    public function getPublicUrlAttribute(): string
    {
        return $this->publicUrl();
    }

    /**
     * Link directo a la confirmación de asistencia, sin pasar por Canva.
     */
    public function rsvpUrl(): string
    {
        return route('invitation.rsvp', $this->token);
    }

    /**
     * Texto listo para compartir por WhatsApp con el link de la invitación.
     */
    public function whatsappUrl(): string
    {
        $greeting = $this->display_name ? "¡Hola {$this->display_name}!" : '¡Hola!';

        $text = $greeting.' Nos casamos 🎉 Esta es tu invitación: '.$this->publicUrl();

        return 'https://wa.me/?text='.rawurlencode($text);
    }

    /**
     * Texto completo de la invitación para copiar y pegar en WhatsApp:
     * saludo con los nombres de los invitados, lugares reservados y los
     * links de su invitación y del sitio web.
     */
    public function shareMessage(): string
    {
        $names = $this->members->pluck('full_name')->filter()->values();

        // Saludo: los nombres de los invitados ("Ana Pérez y Luis García") o,
        // si la invitación aún no tiene miembros, el nombre de la invitación.
        $greeting = $names->isNotEmpty() ? $names->implode(' y ') : $this->display_name;

        // Lugares reservados: 1 o 2 según los invitados de la pareja.
        $places = max($names->count(), 1);
        $placesLabel = $places === 1 ? '1 lugar' : "{$places} lugares";

        return implode("\n\n", [
            "¡Hola, {$greeting}!",
            "Con enorme cariño queremos invitarles a celebrar el día de nuestra boda. Para nosotros es fundamental compartir este momento tan especial rodeados de las personas que más queremos, por lo que hemos reservado {$placesLabel} especialmente a su nombre.",
            "Aunque amamos a los más pequeños, hemos decidido que nuestra boda sea un evento exclusivo para adultos. ¡Agradecemos su comprensión para disfrutar todos juntos de una gran noche!",
            $this->publicUrl(),
            "En el siguiente enlace encontrarán nuestro sitio web, donde podrán consultar todos los detalles del evento, como el código de vestimenta, el itinerario, mesa asignada, preguntas frecuentes y el espacio para confirmar su asistencia:",
            route('home'),
            "¡Estamos muy ilusionados de verlos y celebrar juntos!",
            "Con mucho cariño,",
            WeddingSetting::coupleFirstNames(),
        ]);
    }

    /**
     * Accessor del texto para WhatsApp: share_message.
     */
    public function getShareMessageAttribute(): string
    {
        return $this->shareMessage();
    }
}
