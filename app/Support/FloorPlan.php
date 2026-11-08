<?php

namespace App\Support;

use App\Models\FloorPlanElement;
use App\Models\Guest;
use App\Models\GuestTable;
use App\Models\WeddingSetting;
use Illuminate\Support\Collection;

/**
 * Datos del plano del salón que consumen tanto la vista pública como el panel.
 */
class FloorPlan
{
    /**
     * Construye el plano resaltando los asientos de los invitados indicados.
     *
     * Si no se indica un invitado principal, todos los asientos se marcan como
     * propios (se usa en la página pública de cada invitación). Si se indica,
     * ese invitado va en azul y el resto (su pareja) en color secundario.
     */
    public static function forGuests(Collection $guests, ?int $primaryGuestId = null): array
    {
        $guests = $guests->filter(fn (Guest $guest) => filled($guest->table_group));

        $guests->pluck('table_group')
            ->unique()
            ->each(fn (string $name) => GuestTable::ensureExists($name));

        $tables = GuestTable::orderBy('id')
            ->get()
            ->values()
            ->map(fn (GuestTable $table, int $index) => $table->planLayout($index))
            ->values();

        $tablesByName = $tables->keyBy('name');
        $seats = [];
        $highlightTables = [];

        foreach ($guests as $guest) {
            $table = $tablesByName->get($guest->table_group);

            if (! $table) {
                continue;
            }

            if ($guest->seat_number) {
                $seats[] = [
                    'table_id' => $table['id'],
                    'seat_number' => (int) $guest->seat_number,
                    'guest_name' => $guest->full_name,
                    'is_primary' => $primaryGuestId === null || (int) $guest->id === $primaryGuestId,
                ];
            } else {
                // Sin asiento concreto: se resalta la mesa completa.
                $highlightTables[] = $table['id'];
            }
        }

        return [
            'venue' => WeddingSetting::current()->venueLayout(),
            'tables' => $tables,
            'elements' => FloorPlanElement::orderBy('id')->get([
                'id', 'kind', 'name', 'x', 'y', 'width', 'height', 'rotation', 'color',
            ]),
            'seats' => $seats,
            'highlight_tables' => array_values(array_unique($highlightTables)),
        ];
    }
}
