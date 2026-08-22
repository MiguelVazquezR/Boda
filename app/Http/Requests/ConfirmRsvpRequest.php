<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ruta pública, sin autenticación
    }

    public function rules(): array
    {
        return [
            'attending' => ['required', 'boolean'],
            'rsvp_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'attending.required' => 'Debes indicar si asistirás o no.',
            'rsvp_message.max' => 'El mensaje no debe exceder los 1000 caracteres.',
        ];
    }
}
