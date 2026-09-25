<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeddingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja con el middleware admin en la ruta
    }

    /**
     * Normaliza la CLABE antes de validar: quita espacios, guiones o puntos,
     * para que se pueda pegar tal como aparece en la banca en línea
     * (por ejemplo "012 180 01541225608 6").
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('gift_bank_clabe')) {
            $digits = preg_replace('/\D+/', '', (string) $this->input('gift_bank_clabe'));

            $this->merge(['gift_bank_clabe' => $digits !== '' ? $digits : null]);
        }
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

            // Mesa de regalos → cuenta bancaria (segunda opción de regalo)
            'gift_bank_name' => ['nullable', 'string', 'max:255'],
            'gift_bank_clabe' => ['nullable', 'digits:18'],
            'gift_bank_holder' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_datetime.required' => 'La fecha y hora del evento es obligatoria.',
            'gift_bank_clabe.digits' => 'La CLABE debe tener exactamente 18 dígitos.',
        ];
    }
}
