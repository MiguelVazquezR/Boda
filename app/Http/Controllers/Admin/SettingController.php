<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWeddingSettingRequest;
use App\Models\WeddingSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Muestra el formulario de edición de configuración del sitio.
     */
    public function edit()
    {
        $settings = WeddingSetting::current();

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
            // URL efectiva del sitio de Canva (la de la BD o la de config/wedding.php),
            // para que el campo del panel muestre siempre el link que está activo.
            'canvaUrl' => $settings->invitationArtworkUrl(),
            // Link efectivo de la mesa de regalos (BD o config/wedding.php).
            'giftRegistryUrl' => $settings->giftRegistryUrl(),
        ]);
    }

    /**
     * Guarda los cambios de configuración.
     */
    public function update(UpdateWeddingSettingRequest $request)
    {
        $settings = WeddingSetting::current();

        // Campos de texto (sin archivos)
        $excludedFiles = [
            'how_we_met_photo', 'proposal_photo',
            'ceremony_photo', 'celebration_photo',
            'dress_code_women_dress', 'dress_code_women_shoes',
            'dress_code_women_accessories', 'dress_code_women_other',
            'dress_code_men_suit', 'dress_code_men_shoes',
            'dress_code_men_accessories', 'dress_code_men_other',
            'cover_photo', 'dress_code_image',
        ];
        $data = $request->safe()->except($excludedFiles);

        // Mapa de archivos: [campo_request => columna_bd]
        $fileFields = [
            'how_we_met_photo' => 'how_we_met_photo_path',
            'proposal_photo' => 'proposal_photo_path',
            'ceremony_photo' => 'ceremony_photo_path',
            'celebration_photo' => 'celebration_photo_path',
            'dress_code_women_dress' => 'dress_code_women_dress',
            'dress_code_women_shoes' => 'dress_code_women_shoes',
            'dress_code_women_accessories' => 'dress_code_women_accessories',
            'dress_code_women_other' => 'dress_code_women_other',
            'dress_code_men_suit' => 'dress_code_men_suit',
            'dress_code_men_shoes' => 'dress_code_men_shoes',
            'dress_code_men_accessories' => 'dress_code_men_accessories',
            'dress_code_men_other' => 'dress_code_men_other',
            'cover_photo' => 'cover_photo_path',
            'dress_code_image' => 'dress_code_image_path',
        ];

        foreach ($fileFields as $inputName => $dbColumn) {
            if ($request->hasFile($inputName)) {
                // Eliminar archivo anterior si existe
                if ($settings->{$dbColumn}) {
                    Storage::disk('public')->delete($settings->{$dbColumn});
                }
                $data[$dbColumn] = $request->file($inputName)->store('settings', 'public');
            }
        }

        // Manejar eliminación explícita de imágenes (flags _remove_*)
        foreach ($fileFields as $inputName => $dbColumn) {
            $removeFlag = '_remove_' . $inputName;
            if ($request->input($removeFlag)) {
                if ($settings->{$dbColumn}) {
                    Storage::disk('public')->delete($settings->{$dbColumn});
                }
                $data[$dbColumn] = null;
            }
        }

        $settings->update($data);

        return back()->with('success', 'Configuración actualizada correctamente.');
    }
}
