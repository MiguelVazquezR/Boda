<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmRsvpRequest;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RsvpController extends Controller
{
    /**
     * Busca invitados por nombre (mínimo 2 caracteres).
     * Devuelve máximo 10 resultados para no exponer toda la lista.
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $guests = Guest::search($request->query('q'))
            ->limit(10)
            ->get(['id', 'full_name', 'rsvp_status']);

        return response()->json($guests);
    }

    /**
     * Registra la confirmación o rechazo de asistencia de un invitado.
     */
    public function confirm(ConfirmRsvpRequest $request, Guest $guest)
    {
        $validated = $request->validated();

        if ($validated['attending']) {
            $guest->confirmAttendance(
                message: $validated['rsvp_message'] ?? null,
            );
        } else {
            $guest->declineAttendance(
                message: $validated['rsvp_message'] ?? null,
            );
        }

        return back()->with('success', $validated['attending']
            ? '¡Gracias por confirmar tu asistencia!'
            : 'Hemos registrado que no podrás asistir.');
    }
}
