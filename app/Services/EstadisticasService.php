<?php

namespace App\Services;

use App\Enums\EstadoColectivo;
use App\Models\Colectivo;
use App\Models\Destino;
use App\Models\Pasaje;
use App\Models\Viaje;
use Illuminate\Support\Collection;

/**
 * Estadísticas del panel administrativo, calculadas con Eloquent sobre los datos reales.
 *
 * Diferencias importantes:
 *  - Viaje programado: todavía no salió.
 *  - Viaje realizado: ya pasó su llegada estimada (no importa si vendió pasajes o no).
 *  - Pasaje vendido: existe sólo si el pago se aprobó; se cuenta en la tabla pasajes.
 */
class EstadisticasService
{
    /** Indicadores generales. */
    public function resumen(): array
    {
        return [
            'viajes' => Viaje::count(),
            'programados' => Viaje::programados()->count(),
            'realizados' => Viaje::realizados()->count(),
            'realizados_sin_pasajeros' => Viaje::realizados()->doesntHave('pasajes')->count(),
            'destinos' => Destino::count(),
            'colectivos_operativos' => Colectivo::operativos()->count(),
            'colectivos_baja' => Colectivo::where('estado', EstadoColectivo::Baja)->count(),
            'pasajes' => Pasaje::count(),
            'recaudacion' => (float) Pasaje::sum('precio'),
        ];
    }

    /** Pasajes vendidos y recaudación por destino (Destino -> Viaje -> Pasaje, hasManyThrough). */
    public function ventasPorDestino(): Collection
    {
        return Destino::withCount('pasajes')
            ->withSum('pasajes', 'precio')
            ->orderByDesc('pasajes_count')
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Destino $d) => [
                'nombre' => $d->nombre,
                'pasajes' => $d->pasajes_count,
                'recaudacion' => (float) $d->pasajes_sum_precio,
            ]);
    }

    /** Pasajes vendidos y recaudación según el origen del viaje. */
    public function ventasPorOrigen(): Collection
    {
        return Destino::whereHas('viajesDesde')
            ->withCount('pasajesDesde')
            ->withSum('pasajesDesde', 'precio')
            ->orderByDesc('pasajes_desde_count')
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Destino $d) => [
                'nombre' => $d->nombre,
                'pasajes' => $d->pasajes_desde_count,
                'recaudacion' => (float) $d->pasajes_desde_sum_precio,
            ]);
    }

    /** Cantidad de pasajes vendidos por viaje, con la ocupación sobre la capacidad de sus colectivos. */
    public function pasajesPorViaje(int $limite = 10): Collection
    {
        return Viaje::with(['origen', 'destino'])
            ->withCount('pasajes')
            ->withSum('pasajes', 'precio')
            ->withSum('colectivos', 'capacidad')
            ->orderByDesc('pasajes_count')
            ->orderByDesc('salida')
            ->take($limite)
            ->get()
            ->map(fn (Viaje $v) => [
                'viaje' => $v,
                'pasajes' => $v->pasajes_count,
                'recaudacion' => (float) $v->pasajes_sum_precio,
                'capacidad' => (int) $v->colectivos_sum_capacidad,
                'ocupacion' => $v->colectivos_sum_capacidad > 0
                    ? (int) round($v->pasajes_count * 100 / $v->colectivos_sum_capacidad)
                    : 0,
            ]);
    }
}
