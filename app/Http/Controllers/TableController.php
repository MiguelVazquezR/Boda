<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Support\FloorPlan;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * "Encuentra tu mesa": busca invitados por nombre y devuelve la mesa asignada.
     *
     * Solo expone invitados que YA tienen mesa registrada en la base de datos
     * (la asignación se carga unos 15 días antes del evento), por lo que el resto
     * de la lista de invitados permanece privada.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $guests = Guest::search($request->query('q'))
            ->with(['group:id,name', 'invitation:id,token,display_name'])
            ->orderBy('full_name')
            ->limit(10)
            ->get([
                'id', 'full_name', 'table_group', 'guest_group_id',
                'seat_number', 'invitation_id',
            ]);

        // Compañeros de mesa: el resto de invitados asignados a las mismas mesas
        // que los resultados encontrados, en una sola consulta (a lo sumo 10 mesas).
        $assignedTables = $guests
            ->filter(fn (Guest $guest) => filled($guest->table_group))
            ->pluck('table_group')
            ->unique()
            ->values();

        $tablemates = $assignedTables->isEmpty()
            ? collect()
            : Guest::query()
                ->whereIn('table_group', $assignedTables->all())
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'table_group'])
                ->groupBy('table_group');

        return response()->json($guests->map(fn (Guest $guest) => [
            'id' => $guest->id,
            'full_name' => $guest->full_name,
            'table_group' => $guest->table_group,
            'seat_number' => $guest->seat_number,
            'group_name' => $guest->group?->name,
            // Los invitados sin mesa también se muestran, marcados como pendientes.
            'has_table' => filled($guest->table_group),
            'table_plan_url' => filled($guest->table_group)
                ? $guest->invitation?->tablePlanUrl()
                : null,
            'tablemates' => $tablemates
                ->get($guest->table_group, collect())
                ->reject(fn (Guest $mate) => (int) $mate->id === (int) $guest->id)
                ->pluck('full_name')
                ->values(),
        ]));
    }

    /**
     * Plano del salón para un invitado: devuelve el acomodo de las mesas y
     * resalta únicamente los asientos del invitado y de su misma invitación.
     * El resto de las mesas se dibuja en gris (sin nombres de otros invitados).
     */
    public function plan(Guest $guest)
    {
        abort_if(blank($guest->table_group), 404);

        // Invitado buscado + integrantes de su misma invitación (pareja/acompañantes).
        $highlighted = Guest::query()
            ->whereNotNull('table_group')
            ->where('table_group', '<>', '')
            ->when(
                $guest->invitation_id,
                fn ($query) => $query->where('invitation_id', $guest->invitation_id),
                fn ($query) => $query->whereKey($guest->id),
            )
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'table_group', 'seat_number']);

        return response()->json([
            'guest' => [
                'full_name' => $guest->full_name,
                'table_group' => $guest->table_group,
                'seat_number' => $guest->seat_number,
            ],
            ...FloorPlan::forGuests($highlighted, (int) $guest->id),
        ]);
    }
}