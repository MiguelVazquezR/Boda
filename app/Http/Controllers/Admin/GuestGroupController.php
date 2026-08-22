<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestGroupRequest;
use App\Http\Requests\UpdateGuestGroupRequest;
use App\Models\GuestGroup;

class GuestGroupController extends Controller
{
    /**
     * Crea un nuevo grupo.
     */
    public function store(StoreGuestGroupRequest $request)
    {
        GuestGroup::create($request->validated());

        return back()->with('success', 'Grupo creado correctamente.');
    }

    /**
     * Actualiza un grupo existente.
     */
    public function update(UpdateGuestGroupRequest $request, GuestGroup $group)
    {
        $group->update($request->validated());

        return back()->with('success', 'Grupo actualizado correctamente.');
    }

    /**
     * Elimina un grupo. Los invitados que lo tenían quedan sin grupo asignado.
     */
    public function destroy(GuestGroup $group)
    {
        $group->guests()->update(['guest_group_id' => null]);
        $group->delete();

        return back()->with('success', 'Grupo eliminado correctamente.');
    }
}
