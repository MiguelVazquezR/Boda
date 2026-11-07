<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vínculo entre dos invitados (pareja, matrimonio, familia o acompañante).
 * Sirve para avisar cuando quedan asignados a mesas distintas.
 */
class GuestRelation extends Model
{
    public const TYPES = ['couple', 'marriage', 'family', 'companion'];

    protected $fillable = [
        'guest_id',
        'related_guest_id',
        'type',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function relatedGuest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'related_guest_id');
    }
}
