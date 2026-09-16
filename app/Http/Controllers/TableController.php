<?php

namespace App\Http\Controllers;

use App\Models\Guest;
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
            ->whereNotNull('table_group')
            ->where('table_group', '<>', '')
            ->with('group:id,name')
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name', 'table_group', 'guest_group_id']);

        // Compañeros de mesa: el resto de invitados asignados a las mismas mesas
        // que los resultados encontrados, en una sola consulta (a lo sumo 10 mesas).
        $tablemates = Guest::query()
            ->whereIn('table_group', $guests->pluck('table_group')->unique()->all())
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'table_group'])
            ->groupBy('table_group');

        return response()->json($guests->map(fn (Guest $guest) => [
            'id' => $guest->id,
            'full_name' => $guest->full_name,
            'table_group' => $guest->table_group,
            'group_name' => $guest->group?->name,
            'tablemates' => $tablemates
                ->get($guest->table_group, collect())
                ->reject(fn (Guest $mate) => (int) $mate->id === (int) $guest->id)
                ->pluck('full_name')
                ->values(),
        ]));
    }
}