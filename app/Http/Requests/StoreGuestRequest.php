<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin en ruta
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:150'],
            'last_name' => ['nullable', 'string', 'max:150'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'gender' => ['nullable', Rule::in(['femenino', 'masculino'])],
            'guest_group_id' => ['nullable', 'integer', 'exists:guest_groups,id'],
            'phone' => ['nullable', 'digits:10'],
            'origin' => ['nullable', Rule::in(['foraneo', 'local'])],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:150'],
            'table_group' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre del invitado es obligatorio.',
            'age.min' => 'La edad debe ser un número válido.',
            'age.max' => 'La edad debe ser un número válido.',
            'phone.digits' => 'El celular debe tener exactamente 10 dígitos.',
            'guest_group_id.exists' => 'El grupo seleccionado no es válido.',
        ];
    }
}
