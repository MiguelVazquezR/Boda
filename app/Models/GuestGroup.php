<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestGroup extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Invitados que pertenecen a este grupo.
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'guest_group_id');
    }
}
