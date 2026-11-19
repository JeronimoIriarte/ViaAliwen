<?php

namespace App\Services;

use App\Models\Colectivo;
use App\Models\Viaje;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Calcula la disponibilidad de butacas. Nada de esto se guarda en la base: se calcula
 * cada vez a partir de los pasajes vendidos y las reservas con pago en curso.
 *
 *   ocupadas(V, C) = butacas con pasaje en (V, C) ∪ butacas reservadas por compras vigentes
 *   libres(V, C)   = capacidad de C − ocupadas(V, C)      (0 si C está dado de baja)
 *   V disponible   ⇔ V todavía no salió y algún colectivo operativo de V tiene butacas libres
 *
 * Trabaja sobre relaciones precargadas con Eloquent (with / loadMissing), así un listado
 * completo se resuelve con pocas consultas y sin SQL manual.
 */
class DisponibilidadService
{
    /** Relaciones necesarias para calcular la disponibilidad de un viaje. */
    private const RELACIONES = [
        'colectivos',
        'pasajes:id,viaje_id,colectivo_id,nro_butaca',
        'reservasActivas:id,compra_id,viaje_id,colectivo_id,nro_butaca',
    ];

    /** Precarga las relaciones (en un viaje o en una colección de viajes) si todavía no están cargadas. */
    public function cargar(Viaje|EloquentCollection $viajes): Viaje|EloquentCollection
    {
        return $viajes->loadMissing(self::RELACIONES);
    }

    /** Butacas vendidas en un viaje y colectivo. */
    public function butacasVendidas(Viaje $viaje, Colectivo $colectivo): Collection
    {
        $this->cargar($viaje);

        return $viaje->pasajes->where('colectivo_id', $colectivo->id)->pluck('nro_butaca')->sort()->values();
    }

    /** Butacas retenidas por compras con el pago en curso (todavía no vencidas). */
    public function butacasReservadas(Viaje $viaje, Colectivo $colectivo): Collection
    {
        $this->cargar($viaje);

        return $viaje->reservasActivas->where('colectivo_id', $colectivo->id)->pluck('nro_butaca')->sort()->values();
    }

    /** Butacas no disponibles: vendidas o reservadas. */
    public function butacasOcupadas(Viaje $viaje, Colectivo $colectivo): Collection
    {
        return $this->butacasVendidas($viaje, $colectivo)
            ->merge($this->butacasReservadas($viaje, $colectivo))
            ->unique()->sort()->values();
    }

    /** Cantidad de butacas libres; un colectivo dado de baja no vende, así que tiene 0. */
    public function butacasLibres(Viaje $viaje, Colectivo $colectivo): int
    {
        if (! $colectivo->esOperativo()) {
            return 0;
        }

        return max(0, $colectivo->capacidad - $this->butacasOcupadas($viaje, $colectivo)->count());
    }

    public function butacaLibre(Viaje $viaje, Colectivo $colectivo, int $numero): bool
    {
        return $colectivo->esOperativo()
            && $numero >= 1 && $numero <= $colectivo->capacidad
            && ! $this->butacasOcupadas($viaje, $colectivo)->contains($numero);
    }

    /** Suma de butacas libres entre todos los colectivos operativos del viaje. */
    public function libresTotales(Viaje $viaje): int
    {
        $this->cargar($viaje);

        return $viaje->colectivos->sum(fn (Colectivo $c) => $this->butacasLibres($viaje, $c));
    }

    /** Un viaje se vende si todavía no salió y tiene al menos una butaca libre en un colectivo operativo. */
    public function estaDisponible(Viaje $viaje): bool
    {
        return $viaje->salida->isFuture() && $this->libresTotales($viaje) > 0;
    }

    /**
     * Detalle por colectivo, para el panel y el selector de butacas.
     *
     * @return Collection<int, array{colectivo: Colectivo, vendidas: Collection, reservadas: Collection, libres: int}>
     */
    public function resumen(Viaje $viaje): Collection
    {
        $this->cargar($viaje);

        return $viaje->colectivos->sortBy('numero_interno')->values()->map(fn (Colectivo $c) => [
            'colectivo' => $c,
            'vendidas' => $this->butacasVendidas($viaje, $c),
            'reservadas' => $this->butacasReservadas($viaje, $c),
            'libres' => $this->butacasLibres($viaje, $c),
        ]);
    }

    /**
     * Viajes disponibles para la venta. Recibe una consulta (con los filtros que necesite
     * quien la llama) y devuelve sólo los viajes que cumplen las reglas de disponibilidad.
     */
    public function viajesDisponibles(?Builder $consulta = null, ?int $limite = null): EloquentCollection
    {
        $consulta ??= Viaje::query();

        $viajes = $consulta
            ->programados()
            ->whereHas('colectivos', fn (Builder $q) => $q->operativos())
            ->with(['origen', 'destino'])
            ->orderBy('salida')
            ->get();

        $disponibles = $this->cargar($viajes)->filter(fn (Viaje $v) => $this->libresTotales($v) > 0)->values();

        return $limite ? $disponibles->take($limite) : $disponibles;
    }
}
