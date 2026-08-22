<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin en ruta
    }

    public function rules(): array
    {
        return [
            'time' => ['nullable', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del momento es obligatorio.',
            'image.max' => 'La imagen no debe superar los 5 MB.',
        ];
    }
}
