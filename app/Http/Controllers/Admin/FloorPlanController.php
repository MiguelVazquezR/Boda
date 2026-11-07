<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FloorPlanElement;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\GuestRelation;
use App\Models\GuestTable;
use App\Models\WeddingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Diseñador de salón: reparte a los invitados en mesas sobre un plano.
 *
 * La asignación se guarda en las mismas columnas que usa el resto del sitio
 * (`guests.table_group` y `guests.seat_number`), así que «Encuentra tu mesa»
 * y la sección de invitados siguen funcionando sin cambios.
 */
class FloorPlanController extends Controller
{
    private const SHAPES = ['round', 'square', 'rect'];

    private const DEFAULT_LAYOUT = [
        'round' => [180, 180],
        'square' => [160, 160],
        'rect' => [240, 120],
    ];

    public function index()
    {
        $guests = Guest::with('group:id,name')
            ->orderBy('full_name')
            ->get([
                'id', 'full_name', 'guest_group_id', 'rsvp_status',
                'table_group', 'seat_number',
            ]);

        $catalog = GuestTable::orderBy('id')->get();

        // Autocorrección: las mesas que ya usan los invitados pero que aún no
        // están en el catálogo se registran para poder colocarlas en el plano.
        $guests->pluck('table_group')->filter()->unique()->each(function ($name) use ($catalog) {
            if (! $catalog->contains('name', $name)) {
                $catalog->push(GuestTable::create(['name' => $name]));
            }
        });

        $tables = $catalog->values()
            ->map(fn (GuestTable $table, int $index) => $this->tablePayload($table, $index));

        $settings = WeddingSetting::current();

        return Inertia::render('Admin/FloorPlan/Index', [
            'guests' => $guests->map(fn (Guest $guest) => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'group_id' => $guest->guest_group_id,
                'group_name' => $guest->group?->name,
                'rsvp_status' => $guest->rsvp_status,
                'table_group' => $guest->table_group,
                'seat_number' => $guest->seat_number,
            ])->values(),
            'tables' => $tables,
            'groups' => GuestGroup::orderBy('name')->get(['id', 'name']),
            'elements' => FloorPlanElement::orderBy('id')->get([
                'id', 'kind', 'name', 'x', 'y', 'width', 'height', 'rotation', 'color',
            ]),
            'relations' => GuestRelation::get(['id', 'guest_id', 'related_guest_id', 'type']),
            'venue' => $settings->venueLayout(),
        ]);
    }

    /**
     * Guarda el acomodo del salón: medidas, posición de mesas y elementos.
     */
    public function savePlan(Request $request)
    {
        $data = $request->validate([
            'venue' => ['required', 'array'],
            'venue.title' => ['nullable', 'string', 'max:255'],
            'venue.width' => ['required', 'integer', 'min:200', 'max:10000'],
            'venue.height' => ['required', 'integer', 'min:200', 'max:10000'],
            'venue.gridSize' => ['nullable', 'integer', 'min:5', 'max:200'],
            'venue.showGrid' => ['boolean'],
            'venue.snapToGrid' => ['boolean'],
            'venue.shape' => ['nullable', 'string', 'max:20'],
            'venue.sections' => ['nullable', 'array'],

            'tables' => ['present', 'array'],
            'tables.*.id' => ['required', 'integer', 'exists:guest_tables,id'],
            'tables.*.name' => ['required', 'string', 'max:255'],
            'tables.*.number' => ['nullable', 'integer', 'min:1'],
            'tables.*.shape' => ['required', Rule::in(self::SHAPES)],
            'tables.*.capacity' => ['required', 'integer', 'min:1', 'max:40'],
            'tables.*.x' => ['required', 'integer'],
            'tables.*.y' => ['required', 'integer'],
            'tables.*.width' => ['required', 'integer', 'min:40', 'max:2000'],
            'tables.*.height' => ['required', 'integer', 'min:40', 'max:2000'],
            'tables.*.rotation' => ['nullable', 'integer', 'between:0,359'],
            'tables.*.seat_distribution' => ['nullable', 'array'],

            'elements' => ['present', 'array'],
            'elements.*.id' => ['nullable', 'integer'],
            'elements.*.kind' => ['required', 'string', 'max:50'],
            'elements.*.name' => ['required', 'string', 'max:255'],
            'elements.*.x' => ['required', 'integer'],
            'elements.*.y' => ['required', 'integer'],
            'elements.*.width' => ['required', 'integer', 'min:10', 'max:5000'],
            'elements.*.height' => ['required', 'integer', 'min:10', 'max:5000'],
            'elements.*.rotation' => ['nullable', 'integer', 'between:0,359'],
            'elements.*.color' => ['nullable', 'string', 'max:20'],
        ]);

        $names = collect($data['tables'])->pluck('name')->map(fn ($name) => trim($name));

        if ($names->unique()->count() !== $names->count()) {
            throw ValidationException::withMessages([
                'tables' => 'Hay dos mesas con el mismo nombre.',
            ]);
        }

        $ids = collect($data['tables'])->pluck('id');

        if (GuestTable::whereIn('name', $names)->whereNotIn('id', $ids)->exists()) {
            throw ValidationException::withMessages([
                'tables' => 'Ya existe otra mesa con alguno de esos nombres.',
            ]);
        }

        // No se puede dejar una mesa con menos asientos que invitados sentados.
        foreach ($data['tables'] as $row) {
            $table = GuestTable::findOrFail($row['id']);
            $seated = Guest::where('table_group', $table->name)
                ->whereNotNull('seat_number')
                ->count();

            if ($row['capacity'] < $seated) {
                throw ValidationException::withMessages([
                    'tables' => "La mesa «{$table->name}» tiene {$seated} invitado(s) sentado(s); necesita al menos esos asientos.",
                ]);
            }
        }

        DB::transaction(function () use ($data) {
            WeddingSetting::current()->update(['venue_layout' => $data['venue']]);

            $renames = [];

            foreach ($data['tables'] as $row) {
                $table = GuestTable::findOrFail($row['id']);
                $name = trim($row['name']);

                if ($name !== $table->name) {
                    $renames[] = [
                        'table' => $table,
                        'to' => $name,
                        // Se guardan los ids ahora para que un intercambio de
                        // nombres no arrastre a los invitados de la otra mesa.
                        'guest_ids' => Guest::where('table_group', $table->name)->pluck('id')->all(),
                    ];
                    // Nombre temporal para poder intercambiar nombres entre mesas
                    // sin chocar con la restricción de nombres únicos.
                    $table->update(['name' => '__mesa_'.$table->id.'_'.uniqid()]);
                }

                $table->update([
                    'number' => $row['number'] ?? null,
                    'shape' => $row['shape'],
                    'capacity' => $row['capacity'],
                    'x' => $row['x'],
                    'y' => $row['y'],
                    'width' => $row['width'],
                    'height' => $row['height'],
                    'rotation' => $row['rotation'] ?? 0,
                    'seat_distribution' => $row['seat_distribution'] ?? null,
                ]);
            }

            foreach ($renames as $rename) {
                $rename['table']->update(['name' => $rename['to']]);

                if ($rename['guest_ids'] !== []) {
                    Guest::whereIn('id', $rename['guest_ids'])->update(['table_group' => $rename['to']]);
                }
            }

            $this->syncElements($data['elements']);
        });

        return response()->json([
            'elements' => FloorPlanElement::orderBy('id')->get([
                'id', 'kind', 'name', 'x', 'y', 'width', 'height', 'rotation', 'color',
            ]),
        ]);
    }

    /**
     * Crea una mesa nueva desde el plano.
     */
    public function storeTable(Request $request)
    {
        $data = $request->validate($this->tableRules());

        $table = GuestTable::create($data);

        return response()->json(['table' => $this->tablePayload($table, 0)]);
    }

    /**
     * Elimina una mesa; sus invitados quedan sin mesa ni asiento.
     */
    public function destroyTable(GuestTable $guestTable)
    {
        DB::transaction(function () use ($guestTable) {
            Guest::where('table_group', $guestTable->name)
                ->update(['table_group' => null, 'seat_number' => null]);
            $guestTable->delete();
        });

        return response()->json(['deleted' => true]);
    }

    /**
     * Asigna invitados a una mesa y (opcionalmente) a un asiento concreto.
     */
    public function assign(Request $request)
    {
        $data = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.guest_id' => ['required', 'integer', 'exists:guests,id'],
            'assignments.*.table_group' => ['required', 'string', 'max:255'],
            'assignments.*.seat_number' => ['nullable', 'integer', 'min:1', 'max:40'],
        ]);

        $touched = [];

        DB::transaction(function () use ($data, &$touched) {
            foreach ($data['assignments'] as $row) {
                $name = trim($row['table_group']);
                GuestTable::ensureExists($name);

                if (! empty($row['seat_number'])) {
                    // La silla se reemplaza: quien la ocupaba se queda en la mesa
                    // pero sin asiento, y el cliente recibe el cambio.
                    $displaced = Guest::where('table_group', $name)
                        ->where('seat_number', $row['seat_number'])
                        ->whereKeyNot($row['guest_id'])
                        ->pluck('id');

                    if ($displaced->isNotEmpty()) {
                        Guest::whereIn('id', $displaced)->update(['seat_number' => null]);
                        $touched = array_merge($touched, $displaced->all());
                    }
                }

                Guest::whereKey($row['guest_id'])->update([
                    'table_group' => $name,
                    'seat_number' => $row['seat_number'] ?? null,
                ]);

                $touched[] = $row['guest_id'];
            }
        });

        $guests = Guest::whereIn('id', array_unique($touched))->get([
            'id', 'table_group', 'seat_number',
        ]);

        return response()->json([
            'ok' => true,
            'guests' => $guests->map(fn (Guest $guest) => [
                'id' => $guest->id,
                'table_group' => $guest->table_group,
                'seat_number' => $guest->seat_number,
            ]),
        ]);
    }

    /**
     * Quita la mesa (y el asiento) a uno o varios invitados.
     */
    public function unassign(Request $request)
    {
        $data = $request->validate([
            'guest_ids' => ['required', 'array', 'min:1'],
            'guest_ids.*' => ['integer', 'exists:guests,id'],
        ]);

        Guest::whereIn('id', $data['guest_ids'])
            ->update(['table_group' => null, 'seat_number' => null]);

        return response()->json(['ok' => true]);
    }

    /**
     * Crea vínculos entre invitados (pareja, familia, acompañante…).
     */
    public function storeRelations(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(GuestRelation::TYPES)],
            'guest_ids' => ['required', 'array', 'min:2'],
            'guest_ids.*' => ['integer', 'exists:guests,id'],
        ]);

        $ids = collect($data['guest_ids'])->map(fn ($id) => (int) $id)->unique()->sort()->values();

        for ($i = 0; $i < $ids->count(); $i++) {
            for ($j = $i + 1; $j < $ids->count(); $j++) {
                GuestRelation::firstOrCreate(
                    ['guest_id' => $ids[$i], 'related_guest_id' => $ids[$j]],
                    ['type' => $data['type']],
                );
            }
        }

        return response()->json([
            'relations' => GuestRelation::get(['id', 'guest_id', 'related_guest_id', 'type']),
        ]);
    }

    /**
     * Elimina un vínculo entre invitados.
     */
    public function destroyRelation(GuestRelation $guestRelation)
    {
        $guestRelation->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Alta rápida de un invitado desde el panel del plano.
     */
    public function storeGuest(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'guest_group_id' => ['nullable', 'integer', 'exists:guest_groups,id'],
        ]);

        $guest = Guest::create($data);

        return response()->json([
            'guest' => [
                'id' => $guest->id,
                'full_name' => $guest->full_name,
                'group_id' => $guest->guest_group_id,
                'group_name' => $guest->group?->name,
                'rsvp_status' => $guest->rsvp_status,
                'table_group' => null,
                'seat_number' => null,
            ],
        ]);
    }

    /**
     * Reglas compartidas para crear y editar mesas.
     */
    private function tableRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:guest_tables,name'],
            'number' => ['nullable', 'integer', 'min:1'],
            'shape' => ['required', Rule::in(self::SHAPES)],
            'capacity' => ['required', 'integer', 'min:1', 'max:40'],
            'x' => ['nullable', 'integer'],
            'y' => ['nullable', 'integer'],
            'width' => ['nullable', 'integer', 'min:40', 'max:2000'],
            'height' => ['nullable', 'integer', 'min:40', 'max:2000'],
            'rotation' => ['nullable', 'integer', 'between:0,359'],
            'seat_distribution' => ['nullable', 'array'],
        ];
    }

    /**
     * Estructura que consume el diseñador para dibujar una mesa.
     */
    private function tablePayload(GuestTable $table, int $index): array
    {
        $shape = in_array($table->shape, self::SHAPES, true) ? $table->shape : 'round';
        [$defaultWidth, $defaultHeight] = self::DEFAULT_LAYOUT[$shape];
        $columns = 4;

        return [
            'id' => $table->id,
            'number' => $table->number ?? $index + 1,
            'name' => $table->name,
            'shape' => $shape,
            'capacity' => $table->capacity ?: 8,
            'x' => $table->x ?? 120 + ($index % $columns) * 340,
            'y' => $table->y ?? 120 + intdiv($index, $columns) * 340,
            'width' => $table->width ?: $defaultWidth,
            'height' => $table->height ?: $defaultHeight,
            'rotation' => $table->rotation ?? 0,
            'seat_distribution' => $table->seat_distribution,
        ];
    }

    /**
     * Crea, actualiza y elimina los elementos del salón en una sola pasada.
     */
    private function syncElements(array $rows): void
    {
        $kept = [];

        foreach ($rows as $row) {
            $payload = [
                'kind' => $row['kind'],
                'name' => $row['name'],
                'x' => $row['x'],
                'y' => $row['y'],
                'width' => $row['width'],
                'height' => $row['height'],
                'rotation' => $row['rotation'] ?? 0,
                'color' => $row['color'] ?? null,
            ];

            $element = ! empty($row['id']) ? FloorPlanElement::find($row['id']) : null;

            if ($element) {
                $element->update($payload);
                $kept[] = $element->id;
            } else {
                $kept[] = FloorPlanElement::create($payload)->id;
            }
        }

        FloorPlanElement::whereNotIn('id', $kept)->delete();
    }
}
