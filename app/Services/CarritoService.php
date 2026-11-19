<?php

namespace App\Services;

use App\Models\Viaje;
use App\Models\Viajero;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Carrito de compras guardado en la sesión del viajero.
 * Cada ítem guarda sólo identificadores (viaje, colectivo, butaca): el precio se lee siempre
 * de la base de datos, así nadie puede modificarlo desde el navegador.
 */
class CarritoService
{
    private const CLAVE_SESION = 'carrito';

    public function __construct(
        private Session $sesion,
        private DisponibilidadService $disponibilidad,
    ) {}

    /** @return array<string, array{viaje_id: int, colectivo_id: int, nro_butaca: int}> */
    public function items(): array
    {
        return $this->sesion->get(self::CLAVE_SESION, []);
    }

    public function cantidad(): int
    {
        return count($this->items());
    }

    /** Agrega una butaca al carrito después de validar todas las reglas; si alguna falla, lanza un error de validación. */
    public function agregar(Viajero $viajero, int $viajeId, int $colectivoId, int $nroButaca): void
    {
        $clave = "{$viajeId}-{$colectivoId}-{$nroButaca}";
        $items = $this->items();

        if (isset($items[$clave])) {
            throw ValidationException::withMessages(['nro_butaca' => 'Esa butaca ya está en tu carrito.']);
        }

        if (collect($items)->contains('viaje_id', $viajeId)) {
            throw ValidationException::withMessages(['nro_butaca' => 'Ya tenés una butaca de este viaje en el carrito: el pasaje es nominal, una butaca por viaje.']);
        }

        $nuevo = ['viaje_id' => $viajeId, 'colectivo_id' => $colectivoId, 'nro_butaca' => $nroButaca];
        $errores = $this->validar($viajero, [$clave => $nuevo]);

        if (isset($errores[$clave])) {
            throw ValidationException::withMessages(['nro_butaca' => $errores[$clave]]);
        }

        $items[$clave] = $nuevo;
        $this->sesion->put(self::CLAVE_SESION, $items);
    }

    public function quitar(string $clave): void
    {
        $items = $this->items();
        unset($items[$clave]);
        $this->sesion->put(self::CLAVE_SESION, $items);
    }

    public function vaciar(): void
    {
        $this->sesion->forget(self::CLAVE_SESION);
    }

    /**
     * Arma el detalle para mostrar: datos del viaje, colectivo, butaca, precio actual y, si corresponde,
     * el motivo por el que el ítem ya no se puede comprar.
     */
    public function detalle(Viajero $viajero): Collection
    {
        $items = $this->items();
        $errores = $this->validar($viajero, $items);
        $viajes = Viaje::with(['origen', 'destino', 'colectivos'])
            ->findMany(collect($items)->pluck('viaje_id'))
            ->keyBy('id');

        return collect($items)->map(function (array $item, string $clave) use ($viajes, $errores) {
            $viaje = $viajes[$item['viaje_id']] ?? null;

            return [
                'clave' => $clave,
                'viaje' => $viaje,
                'colectivo' => $viaje?->colectivos->firstWhere('id', $item['colectivo_id']),
                'nro_butaca' => $item['nro_butaca'],
                'precio' => $viaje ? (float) $viaje->precio : 0.0,
                'error' => $errores[$clave] ?? null,
            ];
        })->values();
    }

    /**
     * Reglas que debe cumplir cada ítem para poder comprarse. Se usan al agregar al carrito,
     * al mostrarlo y otra vez al iniciar el pago (dentro de la transacción con bloqueo).
     *
     * @param  array<string, array>  $items
     * @return array<string, string> clave del ítem => motivo del problema
     */
    public function validar(Viajero $viajero, array $items): array
    {
        $viajes = Viaje::findMany(collect($items)->pluck('viaje_id')->unique());
        $this->disponibilidad->cargar($viajes);
        $viajes = $viajes->keyBy('id');

        $conPasaje = $viajero->pasajes()->whereIn('viaje_id', $viajes->keys())->pluck('viaje_id')->all();
        $errores = [];

        foreach ($items as $clave => $item) {
            $viaje = $viajes[$item['viaje_id']] ?? null;
            $colectivo = $viaje?->colectivos->firstWhere('id', $item['colectivo_id']);
            $butaca = (int) $item['nro_butaca'];

            $error = match (true) {
                ! $viaje => 'El viaje ya no está disponible.',
                $viaje->salida->isPast() => 'El viaje ya salió.',
                ! $colectivo => 'Ese colectivo no está asignado a este viaje.',
                ! $colectivo->esOperativo() => "El colectivo {$colectivo->numero_interno} fue dado de baja.",
                $butaca < 1 || $butaca > $colectivo->capacidad => "El colectivo {$colectivo->numero_interno} no tiene la butaca {$butaca}.",
                ! $this->disponibilidad->butacaLibre($viaje, $colectivo, $butaca) => "La butaca {$butaca} ya fue vendida o reservada por otra persona.",
                in_array($viaje->id, $conPasaje) => 'Ya tenés un pasaje para este viaje.',
                default => null,
            };

            if ($error) {
                $errores[$clave] = $error;
            }
        }

        return $errores;
    }
}
