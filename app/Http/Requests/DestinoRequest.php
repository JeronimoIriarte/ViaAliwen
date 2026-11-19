<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DestinoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige rol:admin
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nombre' => trim((string) $this->input('nombre')),
            'destacado' => $this->boolean('destacado'),
        ]);
    }

    public function rules(): array
    {
        return [
            // Al editar, el nombre puede ser el mismo que ya tenía este destino.
            'nombre' => ['required', 'string', 'max:100', Rule::unique('destinos', 'nombre')->ignore($this->route('destino'))],
            'descripcion' => ['required', 'string', 'min:20', 'max:3000'],
            'destacado' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return ['nombre.unique' => 'Ya existe un destino con ese nombre.'];
    }
}
