<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Filtros de búsqueda de viajes. Los usa el listado público (/viajes) y, en la Etapa 8,
 * la búsqueda dinámica por AJAX, que responde JSON con el mismo criterio.
 */
class BuscarViajesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la búsqueda es pública
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['q' => trim((string) $this->input('q'))]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:60'],
            'origen_id' => ['nullable', 'integer', 'exists:destinos,id'],
            'destino_id' => ['nullable', 'integer', 'exists:destinos,id'],
            'fecha' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.after_or_equal' => 'Elegí una fecha de hoy en adelante.',
            'fecha.date_format' => 'La fecha no es válida.',
        ];
    }
}
