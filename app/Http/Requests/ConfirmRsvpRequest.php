<?php

namespace App\Http\Requests;

use App\Models\Guest;
use Illuminate\Contracts\Validation\Validator;
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
            // Token de la invitación digital: se envía cuando el invitado llegó
            // por su link personal (o por el link fijo /rsvp que recuerda la
            // invitación en una cookie). Verifica que confirme SU invitación.
            'invitation_token' => ['nullable', 'string', 'max:16'],
        ];
    }

    public function messages(): array
    {
        return [
            'attending.required' => 'Debes indicar si asistirás o no.',
            'rsvp_message.max' => 'El mensaje no debe exceder los 1000 caracteres.',
        ];
    }

    /**
     * Si llega el token de una invitación, el invitado debe pertenecer a ella.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $token = $this->input('invitation_token');

            if (! $token) {
                return;
            }

            $guest = $this->route('guest');

            if (! $guest instanceof Guest || $guest->invitation?->token !== $token) {
                $validator->errors()->add(
                    'invitation_token',
                    'Esta invitación no corresponde al invitado que intenta confirmar.',
                );
            }
        });
    }
}

