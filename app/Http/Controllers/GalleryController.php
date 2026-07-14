<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGalleryPhotoRequest;
use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\Storage;

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
     * Subida de nueva foto por un invitado (queda en estado pending).
     */
    public function store(StoreGalleryPhotoRequest $request)
    {
        $path = $request->file('image')->store('gallery', 'public');

        GalleryPhoto::create([
            'image_path' => $path,
            'uploader_name' => $request->input('uploader_name'),
            'status' => 'pending',
        ]);

        return back()->with('success', '¡Tu foto se ha subido correctamente! Será visible una vez aprobada.');
    }
}
