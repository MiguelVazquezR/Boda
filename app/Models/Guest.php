<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'full_name',
        'search_slug',
        'age',
        'gender',
        'guest_group_id',
        'invitation_id',
        'phone',
        'origin',
        'state',
        'city',
        'rsvp_status',
        'rsvp_message',
        'rsvp_responded_at',
        'table_group',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'guest_group_id' => 'integer',
            'rsvp_responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Guest $guest) {
            // Mantener full_name sincronizado con first_name + last_name
            if ($guest->isDirty('first_name') || $guest->isDirty('last_name')) {
                $guest->full_name = trim(implode(' ', array_filter([
                    $guest->first_name,
                    $guest->last_name,
                ], fn ($value) => $value !== null && $value !== '')));
            }

            if ($guest->isDirty('full_name')) {
                $guest->search_slug = static::normalizeForSearch($guest->full_name);
            }
        });
    }

    /**
     * Grupo al que pertenece el invitado (amigos del novio, familiares, etc.).
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(GuestGroup::class, 'guest_group_id');
    }

    /**
     * Invitación digital a la que va dirigido el invitado (pareja o persona sola).
     */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * Normaliza un string para búsqueda: sin acentos, minúsculas, sin caracteres especiales.
     */
    public static function normalizeForSearch(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        // Reemplazar caracteres acentuados
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n',
        ];
        $value = strtr($value, $unwanted);

        // Eliminar todo excepto letras, números y espacios
        $value = preg_replace('/[^a-z0-9\s]/', '', $value);

        // Colapsar espacios múltiples
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Scope: búsqueda por nombre normalizado.
     */
    public function scopeSearch($query, string $term)
    {
        $normalized = static::normalizeForSearch($term);

        if (empty($normalized)) {
            return $query->whereRaw('1 = 0'); // sin resultados
        }

        return $query->where('search_slug', 'like', '%'.$normalized.'%');
    }

    /**
     * Scope: filtrar por estado de RSVP.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('rsvp_status', $status);
    }

    /**
     * Registra la confirmación de asistencia del invitado.
     */
    public function confirmAttendance(?string $message): void
    {
        $this->rsvp_status = 'confirmed';
        $this->rsvp_message = $message;
        $this->rsvp_responded_at = now();
        $this->save();
    }

    /**
     * Registra el rechazo de asistencia.
     */
    public function declineAttendance(?string $message): void
    {
        $this->rsvp_status = 'declined';
        $this->rsvp_message = $message;
        $this->rsvp_responded_at = now();
        $this->save();
    }
}
