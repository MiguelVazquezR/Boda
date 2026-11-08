<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mesa del evento.
 *
 * Las mesas viven en dos lugares: este catálogo (permite crear mesas vacías,
 * renombrarlas y eliminarlas) y la columna `guests.table_group`, que guarda el
 * nombre de la mesa asignada a cada invitado.
 */
class GuestTable extends Model
{
    /** Formas válidas para el plano del salón. */
    public const SHAPES = ['round', 'square', 'rect'];

    private const PLAN_SIZES = [
        'round' => [180, 180],
        'square' => [160, 160],
        'rect' => [240, 120],
    ];

    protected $fillable = [
        'name',
        'number',
        'shape',
        'capacity',
        'x',
        'y',
        'width',
        'height',
        'rotation',
        'seat_distribution',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'capacity' => 'integer',
            'x' => 'integer',
            'y' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'rotation' => 'integer',
            'seat_distribution' => 'array',
        ];
    }

    /**
     * Invitados asignados a esta mesa (se enlazan por nombre, no por id).
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'table_group', 'name');
    }

    /**
     * Datos de la mesa para dibujarla en el plano (panel y vista pública).
     * Si la mesa todavía no se ha colocado, se le da una posición inicial
     * en cuadrícula según su índice.
     */
    public function planLayout(int $index = 0, int $columns = 4): array
    {
        $shape = in_array($this->shape, self::SHAPES, true) ? $this->shape : 'round';
        [$defaultWidth, $defaultHeight] = self::PLAN_SIZES[$shape];

        return [
            'id' => $this->id,
            'number' => $this->number ?? $index + 1,
            'name' => $this->name,
            'shape' => $shape,
            'capacity' => $this->capacity ?: 8,
            'x' => $this->x ?? 120 + ($index % $columns) * 340,
            'y' => $this->y ?? 120 + intdiv($index, $columns) * 340,
            'width' => $this->width ?: $defaultWidth,
            'height' => $this->height ?: $defaultHeight,
            'rotation' => $this->rotation ?? 0,
            'seat_distribution' => $this->seat_distribution,
        ];
    }

    /**
     * Registra el nombre de mesa en el catálogo si aún no existe.
     *
     * Se usa al asignar mesas a invitados (masiva o individualmente) y al
     * importar invitados por CSV, para que toda mesa usada sea gestionable.
     */
    public static function ensureExists(?string $name): ?self
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return static::firstOrCreate(['name' => $name]);
    }
}
