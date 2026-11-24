<?php

namespace App\Http\Requests;

use App\Models\Colectivo;
use App\Models\Viaje;
use App\Services\ColectivoService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Alta y edición de viajes.
 *  - La llegada estimada se carga a mano (no se calcula) y debe ser posterior a la salida.
 *  - Si el viaje ya tiene pasajes vendidos, sólo se puede cambiar el precio
 *    (cambiar fecha, origen o destino dejaría inconsistentes los pasajes emitidos).
 */
class ViajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gate::authorize lanza un 403 con el mensaje de la policy si no corresponde.
        $this->viaje()
            ? Gate::authorize('update', $this->viaje())
            : Gate::authorize('create', Viaje::class);

        return true;
    }

    public function viaje(): ?Viaje
    {
        return $this->route('viaje');
    }

    /** ¿Sólo se puede modificar el precio? (viaje existente con pasajes vendidos) */
    public function soloPrecio(): bool
    {
        return (bool) $this->viaje()?->tieneVentas();
    }

    public function rules(): array
    {
        $precio = ['required', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'];

        if ($this->soloPrecio()) {
            return ['precio' => $precio];
        }

        $reglas = [
            'origen_id' => ['required', 'integer', 'exists:destinos,id', 'different:destino_id'],
            'destino_id' => ['required', 'integer', 'exists:destinos,id'],
            'precio' => $precio,
            'salida' => ['required', 'date', 'after:now'],
            'llegada_estimada' => ['required', 'date', 'after:salida'],
        ];

        // Al crear se eligen los colectivos; después se asignan o quitan desde la ficha del viaje.
        if (! $this->viaje()) {
            $reglas['colectivos'] = ['required', 'array', 'min:1'];
            $reglas['colectivos.*'] = ['integer', 'distinct', 'exists:colectivos,id'];
        }

        return $reglas;
    }

    /** Validaciones que necesitan consultar la base: colectivos operativos y sin viajes superpuestos. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // En la validación en vivo (Precognition) las reglas vienen filtradas: este control sólo
                // corresponde si se están validando las fechas o los colectivos.
                $reglas = $validator->getRules();
                $corresponde = isset($reglas['salida']) || isset($reglas['llegada_estimada']) || isset($reglas['colectivos']);

                if ($this->soloPrecio() || ! $corresponde
                    || $validator->errors()->hasAny(['salida', 'llegada_estimada', 'colectivos', 'colectivos.*'])
                    || ! $this->filled(['salida', 'llegada_estimada'])) {
                    return;
                }

                try {
                    $salida = Carbon::parse($this->input('salida'));
                    $llegada = Carbon::parse($this->input('llegada_estimada'));
                } catch (InvalidFormatException) {
                    return; // fecha incompleta mientras se escribe: la informa su propia regla
                }
                $campo = $this->viaje() ? 'salida' : 'colectivos';
                $colectivos = $this->viaje()
                    ? $this->viaje()->colectivos
                    : Colectivo::findMany($this->input('colectivos', []));

                foreach ($colectivos as $colectivo) {
                    if (! $this->viaje() && ! $colectivo->esOperativo()) {
                        $validator->errors()->add($campo, "El colectivo {$colectivo->numero_interno} está dado de baja.");
                    }

                    $otro = app(ColectivoService::class)->viajeSuperpuesto($colectivo, $salida, $llegada, $this->viaje()?->id);
                    if ($otro) {
                        $validator->errors()->add($campo, "El colectivo {$colectivo->numero_interno} ya tiene un viaje a {$otro->destino->nombre} "
                            .'el '.$otro->salida->format('d/m/Y H:i').' que se superpone con este horario.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'origen_id.different' => 'El origen y el destino tienen que ser distintos.',
            'salida.after' => 'La salida tiene que ser posterior al momento actual.',
            'llegada_estimada.after' => 'La llegada estimada tiene que ser posterior a la salida.',
            'colectivos.required' => 'Elegí al menos un colectivo para el viaje.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
