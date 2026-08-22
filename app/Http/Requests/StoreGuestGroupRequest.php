<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin en ruta
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:guest_groups,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.unique' => 'Ya existe un grupo con ese nombre.',
        ];
    }
}
