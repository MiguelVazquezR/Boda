<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeddingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja con el middleware admin en la ruta
    }

    public function rules(): array
    {
        return [
            // Nuestra Historia
            'how_we_met_story' => ['nullable', 'string'],
            'how_we_met_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'proposal_story' => ['nullable', 'string'],
            'proposal_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Cuándo y Dónde
            'event_datetime' => ['required', 'date'],

            // Ceremonia
            'ceremony_title' => ['nullable', 'string', 'max:255'],
            'ceremony_datetime' => ['nullable', 'date'],
            'ceremony_address' => ['nullable', 'string', 'max:500'],
            'ceremony_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'ceremony_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'ceremony_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Celebración
            'celebration_title' => ['nullable', 'string', 'max:255'],
            'celebration_datetime' => ['nullable', 'date'],
            'celebration_address' => ['nullable', 'string', 'max:500'],
            'celebration_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'celebration_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'celebration_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // Código de vestimenta
            'dress_code_general' => ['nullable', 'string'],
            'dress_code_women_dress' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_dress_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_women_shoes' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_shoes_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_women_accessories' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_accessories_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_women_other' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_women_other_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_suit' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_suit_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_shoes' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_shoes_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_accessories' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_accessories_desc' => ['nullable', 'string', 'max:255'],
            'dress_code_men_other' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_men_other_desc' => ['nullable', 'string', 'max:255'],

            // Portada
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'dress_code_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],

            // General
            'rsvp_deadline' => ['nullable', 'date'],
            'canva_url' => ['nullable', 'url', 'max:500'],
            'gift_registry_url' => ['nullable', 'url', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_datetime.required' => 'La fecha y hora del evento es obligatoria.',
        ];
    }
}
