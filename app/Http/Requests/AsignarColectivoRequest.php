<?php

namespace App\Http\Requests;

use App\Models\Colectivo;
use App\Models\Viaje;
use App\Services\ColectivoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Asignar (attach) un colectivo a un viaje existente.
 */
class AsignarColectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->viaje()); // sólo el admin que cargó el viaje

        return true;
    }

    public function viaje(): Viaje
    {
        return $this->route('viaje');
    }

    public function rules(): array
    {
        return [
            'colectivo_id' => ['required', 'integer', 'exists:colectivos,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $viaje = $this->viaje();
                $colectivo = Colectivo::find($this->input('colectivo_id'));

                if ($viaje->salida->isPast()) {
                    $validator->errors()->add('colectivo_id', 'No se pueden asignar colectivos a un viaje que ya salió.');
                } elseif (! $colectivo->esOperativo()) {
                    $validator->errors()->add('colectivo_id', "El colectivo {$colectivo->numero_interno} está dado de baja.");
                } elseif ($viaje->colectivos()->whereKey($colectivo->id)->exists()) {
                    $validator->errors()->add('colectivo_id', "El colectivo {$colectivo->numero_interno} ya está asignado a este viaje.");
                } elseif ($otro = app(ColectivoService::class)->viajeSuperpuesto($colectivo, $viaje->salida, $viaje->llegada_estimada, $viaje->id)) {
                    $validator->errors()->add('colectivo_id', "El colectivo {$colectivo->numero_interno} ya tiene un viaje a {$otro->destino->nombre} "
                        .'el '.$otro->salida->format('d/m/Y H:i').' que se superpone con este.');
                }
            },
        ];
    }
}
