<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleItemRequest;
use App\Http\Requests\UpdateScheduleItemRequest;
use App\Models\ScheduleItem;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    /**
     * Gestión del itinerario (sección "Tiempos").
     */
    public function index()
    {
        return Inertia::render('Admin/Schedule/Index', [
            'items' => ScheduleItem::ordered()->get(),
        ]);
    }

    /**
     * Crea un nuevo momento del itinerario (con imagen opcional).
     */
    public function store(StoreScheduleItemRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('schedule', 'public');
        }

        $data['sort_order'] = (ScheduleItem::max('sort_order') ?? 0) + 1;
        $data['is_active'] = $request->boolean('is_active', true);

        ScheduleItem::create($data);

        return back()->with('success', 'Momento del itinerario agregado correctamente.');
    }

    /**
     * Actualiza un momento (permite reemplazar la imagen).
     */
    public function update(UpdateScheduleItemRequest $request, ScheduleItem $item)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', $item->is_active);

        if ($request->hasFile('image')) {
            if ($item->image_path) {
                Storage::disk('public')->delete($item->image_path);
            }
            $data['image_path'] = $request->file('image')->store('schedule', 'public');
        }

        $item->update($data);

        return back()->with('success', 'Momento del itinerario actualizado correctamente.');
    }

    /**
     * Elimina un momento (archivo + registro).
     */
    public function destroy(ScheduleItem $item)
    {
        if ($item->image_path) {
            Storage::disk('public')->delete($item->image_path);
        }
        $item->delete();

        return back()->with('success', 'Momento del itinerario eliminado correctamente.');
    }

    /**
     * Reordena un momento (subir/bajar) intercambiando su sort_order con el vecino.
     */
    public function move(ScheduleItem $item)
    {
        $direction = request()->input('direction', 'up');

        $neighbor = $direction === 'up'
            ? ScheduleItem::where('sort_order', '<', $item->sort_order)->orderByDesc('sort_order')->first()
            : ScheduleItem::where('sort_order', '>', $item->sort_order)->orderBy('sort_order')->first();

        if ($neighbor) {
            [$item->sort_order, $neighbor->sort_order] = [$neighbor->sort_order, $item->sort_order];
            $item->save();
            $neighbor->save();
        }

        return back()->with('success', 'Orden del itinerario actualizado.');
    }
}
