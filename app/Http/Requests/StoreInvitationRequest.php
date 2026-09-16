<?php

namespace App\Http\Requests;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La autorización se maneja con el middleware admin en la ruta
    }

    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:120'],
            'members' => ['required', 'array', 'min:1', 'max:'.Invitation::MAX_MEMBERS],
            'members.*' => ['integer', 'distinct', Rule::exists('guests', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'members.required' => 'Selecciona al menos un invitado para la invitación.',
            'members.min' => 'Selecciona al menos un invitado para la invitación.',
            'members.max' => 'Una invitación puede tener como máximo :max invitados (una pareja).',
            'members.*.distinct' => 'El mismo invitado está seleccionado dos veces.',
            'members.*.exists' => 'Alguno de los invitados seleccionados ya no existe.',
            'display_name.max' => 'El nombre para mostrar no debe exceder los 120 caracteres.',
        ];
    }
}
