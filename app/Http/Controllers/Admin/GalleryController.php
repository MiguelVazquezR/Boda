<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use App\Models\WeddingSetting;
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
            'autoApprove' => (bool) WeddingSetting::current()->gallery_auto_approve,
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
     * Aprobar de una vez todas las fotos pendientes.
     */
    public function approveAll()
    {
        $count = GalleryPhoto::pending()->update(['status' => 'approved']);

        return back()->with('success', $count > 0
            ? "{$count} foto(s) aprobada(s) correctamente."
            : 'No hay fotos pendientes por aprobar.');
    }

    /**
     * Activar o desactivar la publicación de fotos sin aprobación.
     */
    public function toggleAutoApprove()
    {
        $data = request()->validate([
            'auto_approve' => ['required', 'boolean'],
        ]);

        WeddingSetting::current()->update(['gallery_auto_approve' => $data['auto_approve']]);

        return back()->with('success', $data['auto_approve']
            ? 'Las fotos nuevas se publicarán al instante, sin aprobación.'
            : 'Las fotos nuevas requerirán aprobación antes de publicarse.');
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
