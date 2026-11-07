<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ruta pública
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'uploader_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Debes seleccionar al menos una imagen.',
            'images.min' => 'Debes seleccionar al menos una imagen.',
            'images.*.image' => 'Todos los archivos deben ser imágenes (jpg, png, webp).',
            'images.*.max' => 'Cada imagen no debe superar los 10 MB.',
        ];
    }
}
