<?php

namespace App\Services;

use App\Models\Colectivo;
use App\Models\Reserva;
use App\Models\Viaje;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio de los colectivos. Es el único lugar donde se mantiene sincronizada
 * la capacidad con las butacas (cada colectivo tiene butacas numeradas 1..capacidad).
 */
class ColectivoService
{
    /** Crea el colectivo y genera sus butacas en una misma transacción. */
    public function crear(array $datos): Colectivo
    {
        return DB::transaction(function () use ($datos) {
            $colectivo = Colectivo::create($datos);
            $this->generarButacas($colectivo, 1, $colectivo->capacidad);

            return $colectivo;
        });
    }

    /**
     * Actualiza el colectivo. Si cambia la capacidad, agrega o quita butacas.
     * No se puede achicar si alguna de las butacas que desaparecerían tiene un pasaje o una reserva vigente.
     */
    public function actualizar(Colectivo $colectivo, array $datos): Colectivo
    {
        return DB::transaction(function () use ($colectivo, $datos) {
            $anterior = $colectivo->capacidad;
            $nueva = (int) $datos['capacidad'];

            if ($nueva < $anterior) {
                $conPasajes = $colectivo->pasajes()->where('nro_butaca', '>', $nueva)->exists();
                $conReservas = Reserva::where('colectivo_id', $colectivo->id)
                    ->where('nro_butaca', '>', $nueva)
                    ->whereHas('compra', fn (Builder $q) => $q->vigentes())
                    ->exists();

                if ($conPasajes || $conReservas) {
                    throw ValidationException::withMessages([
                        'capacidad' => "No se puede reducir a {$nueva} butacas: hay pasajes vendidos o reservados en butacas con número mayor.",
                    ]);
                }

                $colectivo->butacas()->where('numero', '>', $nueva)->delete();
            }

            $colectivo->update($datos);

            if ($nueva > $anterior) {
                $this->generarButacas($colectivo, $anterior + 1, $nueva);
            }

            return $colectivo;
        });
    }

    /** Sólo se elimina un colectivo que nunca se asignó a un viaje; si no, se da de baja. */
    public function eliminar(Colectivo $colectivo): void
    {
        if ($colectivo->viajes()->exists()) {
            throw ValidationException::withMessages([
                'colectivo' => "El colectivo {$colectivo->numero_interno} tiene viajes asignados: no se puede eliminar. Podés darlo de baja.",
            ]);
        }

        $colectivo->delete(); // sus butacas se borran en cascada
    }

    /**
     * Devuelve otro viaje del colectivo que se superpone con el horario indicado (o null si está libre).
     * Dos viajes se superponen si uno sale antes de que el otro llegue, y viceversa.
     */
    public function viajeSuperpuesto(Colectivo $colectivo, DateTimeInterface $salida, DateTimeInterface $llegada, ?int $excluirViajeId = null): ?Viaje
    {
        return $colectivo->viajes()
            ->with('destino')
            ->where('salida', '<', $llegada)
            ->where('llegada_estimada', '>', $salida)
            ->when($excluirViajeId, fn ($q) => $q->where('viajes.id', '!=', $excluirViajeId))
            ->first();
    }

    /** Crea las butacas numeradas desde $desde hasta $hasta (inclusive). */
    protected function generarButacas(Colectivo $colectivo, int $desde, int $hasta): void
    {
        if ($hasta < $desde) {
            return;
        }

        $colectivo->butacas()->createMany(
            collect(range($desde, $hasta))->map(fn (int $numero) => ['numero' => $numero])->all()
        );
    }
}
