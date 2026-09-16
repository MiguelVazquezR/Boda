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
    ];

    /**
     * El link público se incluye siempre en la serialización (panel de invitados
     * y modal de detalle lo muestran sin cálculos extra en el frontend).
     */
    protected $appends = [
        'public_url',
    ];

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
     * Nombre sugerido para mostrar en la invitación a partir de sus miembros:
     * "Ana & Luis" para una pareja, o el nombre de la persona si va sola.
     */
    public static function suggestDisplayName(Collection $members): string
    {
        $names = $members
            ->map(fn (Guest $guest) => trim((string) ($guest->first_name ?: $guest->full_name)))
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
}
