<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGuestRequest;
use App\Http\Requests\UpdateGuestRequest;
use App\Models\Guest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GuestController extends Controller
{
    /**
     * Listado de invitados con filtros por estado de RSVP y búsqueda.
     */
    public function index(Request $request)
    {
        $guests = Guest::query()
            ->when($request->query('status'), fn ($q, $status) => $q->byStatus($status))
            ->when($request->query('search'), fn ($q, $search) => $q->search($search))
            ->orderBy('full_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Guests/Index', [
            'guests' => $guests,
            'filters' => $request->only(['status', 'search']),
            'statusCounts' => [
                'pending' => Guest::byStatus('pending')->count(),
                'confirmed' => Guest::byStatus('confirmed')->count(),
                'declined' => Guest::byStatus('declined')->count(),
                'total' => Guest::count(),
            ],
        ]);
    }

    /**
     * Alta manual de un invitado.
     */
    public function store(StoreGuestRequest $request)
    {
        Guest::create($request->validated());

        return back()->with('success', 'Invitado agregado correctamente.');
    }

    /**
     * Edición de un invitado.
     */
    public function update(UpdateGuestRequest $request, Guest $guest)
    {
        $guest->update($request->validated());

        return back()->with('success', 'Invitado actualizado correctamente.');
    }

    /**
     * Eliminación de un invitado.
     */
    public function destroy(Guest $guest)
    {
        $guest->delete();

        return back()->with('success', 'Invitado eliminado correctamente.');
    }

    /**
     * Importación masiva de invitados vía CSV.
     * Espera columnas: full_name, allowed_passes (opcional).
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Leer encabezados
        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);
            return back()->with('error', 'El archivo CSV está vacío o no tiene encabezados.');
        }

        // Normalizar encabezados
        $headers = array_map(fn ($h) => mb_strtolower(trim($h)), $headers);

        $created = 0;
        $errors = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);

            $fullName = trim($data['full_name'] ?? '');
            if (empty($fullName)) {
                $errors++;
                continue;
            }

            $allowedPasses = isset($data['allowed_passes']) && is_numeric($data['allowed_passes'])
                ? max(1, (int) $data['allowed_passes'])
                : 1;

            Guest::create([
                'full_name' => $fullName,
                'allowed_passes' => $allowedPasses,
            ]);

            $created++;
        }

        fclose($handle);

        return back()->with('success', "Importación completada: {$created} invitados creados, {$errors} errores.");
    }
}
