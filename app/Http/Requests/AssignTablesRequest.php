<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin en la ruta
    }

    public function rules(): array
    {
        return [
            'guest_ids' => ['required', 'array', 'min:1'],
            'guest_ids.*' => ['integer', 'exists:guests,id'],
            'table_group' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_ids.required' => 'Selecciona al menos un invitado.',
            'guest_ids.min' => 'Selecciona al menos un invitado.',
            'guest_ids.*.exists' => 'Alguno de los invitados seleccionados ya no existe.',
        ];
    }
}