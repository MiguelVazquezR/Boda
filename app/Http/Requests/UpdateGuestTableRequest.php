<?php

namespace App\Http\Requests;

use App\Models\Guest;
use App\Models\GuestTable;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Middleware admin en la ruta
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('guest_tables', 'name')->ignore($this->route('guestTable')),
                $this->tableNameIsFree(),
            ],
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

    /**
     * El nuevo nombre no debe estar en uso por invitados de otra mesa.
     * (Si no, al renombrar se mezclarían dos mesas distintas.)
     */
    private function tableNameIsFree(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            /** @var GuestTable|null $table */
            $table = $this->route('guestTable');
            $name = trim((string) $value);

            if ($table && $name === $table->name) {
                return;
            }

            if (Guest::where('table_group', $name)->exists()) {
                $fail('Ya existe una mesa con ese nombre.');
            }
        };
    }
}
