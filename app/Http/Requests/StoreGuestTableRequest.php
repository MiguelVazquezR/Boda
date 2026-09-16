<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin en la ruta
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:guest_tables,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre o número de la mesa.',
            'name.unique' => 'Ya existe una mesa con ese nombre.',
            'name.max' => 'El nombre de la mesa no puede tener más de 100 caracteres.',
        ];
    }
}
