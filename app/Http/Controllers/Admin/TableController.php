<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTablesRequest;
use App\Http\Requests\StoreGuestTableRequest;
use App\Http\Requests\UpdateGuestTableRequest;
use App\Models\Guest;
use App\Models\GuestTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TableController extends Controller
{
    /**
     * Panel de asignación de mesas.
     *
     * Muestra todos los invitados con su mesa actual (o "sin asignar") y un
     * resumen por mesa, para poder repartir las mesas unos días antes del evento.
     */
    public function index(Request $request)
    {
        $guests = Guest::with('group:id,name')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'guest_group_id', 'rsvp_status', 'table_group', 'city', 'origin']);

        $assigned = $guests->filter(fn (Guest $guest) => filled($guest->table_group));
        $byTable = $assigned->groupBy('table_group');

        // Catálogo de mesas: incluye las mesas vacías creadas desde el gestor.
        $catalog = GuestTable::get(['id', 'name']);

        // Autocorrección: registra las mesas que ya usan los invitados pero que
        // todavía no están en el catálogo (por ejemplo, mesas heredadas).
        $byTable->keys()->each(function ($name) use ($catalog) {
            $name = (string) $name;

            if (! $catalog->contains('name', $name)) {
                $catalog->push(GuestTable::create(['name' => $name]));
            }
        });

        $tables = $catalog
            ->map(function (GuestTable $table) use ($byTable) {
                $group = $byTable->get($table->name, collect());

                return [
                    'id' => $table->id,
                    'name' => $table->name,
                    'total' => $group->count(),
                    'confirmed' => $group->where('rsvp_status', 'confirmed')->count(),
                    'guests' => $group->pluck('full_name')->values(),
                ];
            })
            ->sortBy(fn (array $table) => $table['name'], SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return Inertia::render('Admin/Tables/Index', [
            'guests' => $guests,
            'tables' => $tables,
            'stats' => [
                'tables' => $tables->count(),
                'assigned' => $assigned->count(),
                'unassigned' => $guests->count() - $assigned->count(),
            ],
        ]);
    }

    /**
     * Asigna la misma mesa a uno o varios invitados (o quita la mesa si va vacía).
     *
     * Los invitados que ya tenían otra mesa se mueven a la última mesa asignada.
     */
    public function assign(AssignTablesRequest $request)
    {
        $data = $request->validated();
        $table = trim((string) ($data['table_group'] ?? ''));

        $updated = Guest::whereIn('id', $data['guest_ids'])
            ->update(['table_group' => $table === '' ? null : $table]);

        if ($table !== '') {
            // Toda mesa usada queda registrada para poder gestionarla después.
            GuestTable::ensureExists($table);
        }

        return back()->with('success', $table === ''
            ? "Se quitó la mesa a {$updated} invitado(s)."
            : "Mesa «{$table}» asignada a {$updated} invitado(s).");
    }

    /**
     * Crea una mesa (aunque todavía no tenga invitados).
     */
    public function store(StoreGuestTableRequest $request)
    {
        $name = trim((string) $request->validated()['name']);

        GuestTable::firstOrCreate(['name' => $name]);

        return back()->with('success', "Mesa «{$name}» creada.");
    }

    /**
     * Renombra una mesa y mueve a sus invitados al nuevo nombre.
     */
    public function update(UpdateGuestTableRequest $request, GuestTable $guestTable)
    {
        $name = trim((string) $request->validated()['name']);
        $previous = $guestTable->name;

        if ($name === $previous) {
            return back()->with('success', "La mesa «{$name}» no tuvo cambios.");
        }

        $moved = Guest::where('table_group', $previous)->count();

        DB::transaction(function () use ($guestTable, $previous, $name) {
            $guestTable->update(['name' => $name]);
            Guest::where('table_group', $previous)->update(['table_group' => $name]);
        });

        return back()->with('success', $moved > 0
            ? "Mesa «{$previous}» renombrada a «{$name}». Se movieron {$moved} invitado(s)."
            : "Mesa «{$previous}» renombrada a «{$name}».");
    }

    /**
     * Elimina una mesa. Sus invitados quedan sin mesa asignada.
     */
    public function destroy(GuestTable $guestTable)
    {
        $name = $guestTable->name;
        $released = Guest::where('table_group', $name)->count();

        DB::transaction(function () use ($guestTable, $name) {
            Guest::where('table_group', $name)->update(['table_group' => null]);
            $guestTable->delete();
        });

        return back()->with('success', $released > 0
            ? "Mesa «{$name}» eliminada. {$released} invitado(s) quedaron sin mesa."
            : "Mesa «{$name}» eliminada.");
    }
}