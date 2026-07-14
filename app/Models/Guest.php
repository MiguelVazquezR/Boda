<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Guest extends Model
{
    protected $fillable = [
        'full_name',
        'search_slug',
        'allowed_passes',
        'rsvp_status',
        'confirmed_passes',
        'confirmed_by_name',
        'rsvp_message',
        'rsvp_responded_at',
        'table_group',
    ];

    protected function casts(): array
    {
        return [
            'allowed_passes' => 'integer',
            'confirmed_passes' => 'integer',
            'rsvp_responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Guest $guest) {
            if ($guest->isDirty('full_name')) {
                $guest->search_slug = static::normalizeForSearch($guest->full_name);
            }
        });
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
    public function confirmAttendance(int $passes, ?string $confirmedByName, ?string $message): void
    {
        $this->rsvp_status = 'confirmed';
        $this->confirmed_passes = $passes;
        $this->confirmed_by_name = $confirmedByName;
        $this->rsvp_message = $message;
        $this->rsvp_responded_at = now();
        $this->save();
    }

    /**
     * Registra el rechazo de asistencia.
     */
    public function declineAttendance(?string $confirmedByName, ?string $message): void
    {
        $this->rsvp_status = 'declined';
        $this->confirmed_passes = 0;
        $this->confirmed_by_name = $confirmedByName;
        $this->rsvp_message = $message;
        $this->rsvp_responded_at = now();
        $this->save();
    }
}
