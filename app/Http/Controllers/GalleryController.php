<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGalleryPhotoRequest;
use App\Models\GalleryPhoto;
use App\Models\WeddingSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    /**
     * Lista de fotos aprobadas (puede usarse como endpoint independiente
     * o consumirse desde el prop de PageController@home).
     */
    public function index()
    {
        return response()->json(
            GalleryPhoto::approved()->latest()->get()
        );
    }

    /**
     * Galería pública completa.
     */
    public function showGallery()
    {
        return Inertia::render('Gallery/Index', [
            'galleryPhotos' => GalleryPhoto::approved()->latest()->get(),
        ]);
    }

    /**
     * Subida de fotos por un invitado (quedan pendientes de moderación).
     */
    public function store(StoreGalleryPhotoRequest $request)
    {
        $autoApprove = (bool) WeddingSetting::current()->gallery_auto_approve;
        $count = 0;

        foreach ($request->file('images') as $image) {
            GalleryPhoto::create([
                'image_path' => $image->store('gallery', 'public'),
                'uploader_name' => $request->input('uploader_name'),
                'status' => $autoApprove ? 'approved' : 'pending',
            ]);
            $count++;
        }

        return back()->with('success', $autoApprove
            ? "¡Tus {$count} foto(s) ya están publicadas en la galería!"
            : "¡Tus {$count} foto(s) se han subido correctamente! Serán visibles una vez aprobadas.");
    }

    /**
     * Descarga una foto aprobada de la galería pública.
     */
    public function download(GalleryPhoto $photo)
    {
        abort_unless($photo->status === 'approved', 404);

        return Storage::disk('public')->download($photo->image_path);
    }
}
