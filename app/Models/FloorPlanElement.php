<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Elemento decorativo del plano del salón (pista de baile, barra, escenario…).
 */
class FloorPlanElement extends Model
{
    protected $fillable = [
        'kind',
        'name',
        'x',
        'y',
        'width',
        'height',
        'rotation',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'x' => 'integer',
            'y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'rotation' => 'integer',
        ];
    }
}
