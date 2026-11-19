<?php

namespace App\Http\Requests;

use App\Enums\EstadoColectivo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ColectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la ruta ya exige rol:admin
    }

    /** Patente en mayúsculas y sin espacios ni guiones (ej. "ab 123 cd" -> "AB123CD"). */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'patente' => strtoupper(preg_replace('/[\s-]/', '', (string) $this->input('patente'))),
        ]);
    }

    public function rules(): array
    {
        $colectivo = $this->route('colectivo');

        return [
            'numero_interno' => ['required', 'integer', 'min:1', 'max:9999', Rule::unique('colectivos', 'numero_interno')->ignore($colectivo)],
            // Formatos argentinos: ABC123 (anterior) o AB123CD (Mercosur).
            'patente' => ['required', 'regex:/^([A-Z]{3}\d{3}|[A-Z]{2}\d{3}[A-Z]{2})$/', Rule::unique('colectivos', 'patente')->ignore($colectivo)],
            'capacidad' => ['required', 'integer', 'min:10', 'max:60'],
            'estado' => ['required', Rule::enum(EstadoColectivo::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'patente.regex' => 'La patente debe tener el formato ABC123 o AB123CD.',
            'patente.unique' => 'Ya hay un colectivo con esa patente.',
            'numero_interno.unique' => 'Ya hay un colectivo con ese número interno.',
        ];
    }
}
