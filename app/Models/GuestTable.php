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
    protected $fillable = ['name'];

    /**
     * Invitados asignados a esta mesa (se enlazan por nombre, no por id).
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class, 'table_group', 'name');
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
