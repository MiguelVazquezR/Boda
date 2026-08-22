<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    /**
     * Panel de moderación: muestra fotos filtrables por estado.
     */
    public function index()
    {
        return Inertia::render('Admin/Gallery/Index', [
            'photos' => GalleryPhoto::query()
                ->when(request()->query('status'), fn ($q, $status) => $q->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'counts' => [
                'total' => GalleryPhoto::count(),
                'pending' => GalleryPhoto::pending()->count(),
                'approved' => GalleryPhoto::approved()->count(),
                'rejected' => GalleryPhoto::where('status', 'rejected')->count(),
            ],
        ]);
    }

    /**
     * Aprobar una foto.
     */
    public function approve(GalleryPhoto $photo)
    {
        $photo->update(['status' => 'approved']);

        return back()->with('success', 'Foto aprobada.');
    }

    /**
     * Rechazar una foto.
     */
    public function reject(GalleryPhoto $photo)
    {
        $photo->update(['status' => 'rejected']);

        return back()->with('success', 'Foto rechazada.');
    }

    /**
     * Eliminar una foto (archivo + registro).
     */
    public function destroy(GalleryPhoto $photo)
    {
        Storage::disk('public')->delete($photo->image_path);
        $photo->delete();

        return back()->with('success', 'Foto eliminada permanentemente.');
    }

    /**
     * Subida de fotos desde el panel admin (auto-aprobadas).
     */
    public function store()
    {
        request()->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $count = 0;
        foreach (request()->file('images') as $file) {
            GalleryPhoto::create([
                'image_path' => $file->store('gallery', 'public'),
                'uploader_name' => 'Admin',
                'status' => 'approved',
            ]);
            $count++;
        }

        return back()->with('success', "{$count} foto(s) subida(s) y aprobada(s) correctamente.");
    }

    /**
     * Descargar una foto individual.
     */
    public function download(GalleryPhoto $photo)
    {
        return Storage::disk('public')->download($photo->image_path);
    }
}
