<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos personales que el viajero puede modificar. DNI y correo no están en las reglas:
 * aunque alguien los envíe, validated() no los devuelve y no se guardan.
 */
class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Sólo un viajero autenticado (la ruta además está protegida con rol:viajero).
        return $this->user()?->viajero !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'apellido' => trim((string) $this->input('apellido')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80', "regex:/^[\pL\s'-]+$/u"],
            'apellido' => ['required', 'string', 'max:80', "regex:/^[\pL\s'-]+$/u"],
            'fecha_nacimiento' => ['required', 'date', 'before:today', 'after:1900-01-01'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'El nombre sólo puede contener letras, espacios, apóstrofos y guiones.',
            'apellido.regex' => 'El apellido sólo puede contener letras, espacios, apóstrofos y guiones.',
        ];
    }
}
