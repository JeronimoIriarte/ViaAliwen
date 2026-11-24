<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegistroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Normaliza los datos antes de validar: email en minúsculas y DNI sin puntos ni espacios. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'dni' => preg_replace('/[\s.]/', '', (string) $this->input('dni')),
            'nombre' => trim((string) $this->input('nombre')),
            'apellido' => trim((string) $this->input('apellido')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80', "regex:/^[\pL\s'-]+$/u"],
            'apellido' => ['required', 'string', 'max:80', "regex:/^[\pL\s'-]+$/u"],
            'dni' => ['required', 'digits_between:7,8', 'unique:viajeros,dni'],
            'fecha_nacimiento' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            // La coincidencia se valida en la repetición (no con "confirmed" en password): así el error
            // aparece debajo de "Repetí la contraseña" y no mientras se escribe la primera.
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre sólo puede contener letras, espacios, apóstrofos y guiones.',
            'apellido.regex' => 'El apellido sólo puede contener letras, espacios, apóstrofos y guiones.',
            'dni.unique' => 'Ya hay una cuenta registrada con ese DNI.',
            'email.unique' => 'Ya hay una cuenta registrada con ese correo electrónico.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'password_confirmation.required' => 'Repetí la contraseña.',
            'password_confirmation.same' => 'Las contraseñas no coinciden.',
        ];
    }
}
