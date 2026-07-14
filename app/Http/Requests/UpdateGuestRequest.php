<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'allowed_passes' => ['required', 'integer', 'min:1', 'max:255'],
            'table_group' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'El nombre del invitado es obligatorio.',
            'allowed_passes.required' => 'El número de pases es obligatorio.',
            'allowed_passes.min' => 'El invitado debe tener al menos 1 pase.',
        ];
    }
}
