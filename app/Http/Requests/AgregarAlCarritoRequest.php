<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Datos para agregar una butaca al carrito. El precio NO viaja en el formulario:
 * se toma de la base de datos al mostrar el carrito y al iniciar el pago.
 */
class AgregarAlCarritoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->viajero !== null; // la ruta además exige rol:viajero
    }

    public function rules(): array
    {
        return [
            'viaje_id' => ['required', 'integer', 'exists:viajes,id'],
            'colectivo_id' => ['required', 'integer', 'exists:colectivos,id'],
            'nro_butaca' => ['required', 'integer', 'min:1', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return ['nro_butaca.required' => 'Elegí una butaca en el mapa del colectivo.'];
    }
}
