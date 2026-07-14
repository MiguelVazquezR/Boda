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
        $guest = $this->route('guest');
        $maxPasses = $guest ? $guest->allowed_passes : 255;

        return [
            'attending' => ['required', 'boolean'],
            'confirmed_passes' => ['required_if:attending,true', 'integer', 'min:0', 'max:'.$maxPasses],
            'confirmed_by_name' => ['nullable', 'string', 'max:255'],
            'rsvp_message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'attending.required' => 'Debes indicar si asistirás o no.',
            'confirmed_passes.required_if' => 'Debes indicar cuántas personas asistirán.',
            'confirmed_passes.max' => 'No puedes confirmar más pases de los que tienes asignados.',
            'rsvp_message.max' => 'El mensaje no debe exceder los 1000 caracteres.',
        ];
    }
}
