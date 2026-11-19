<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuscarViajesRequest;
use App\Models\Destino;
use App\Models\Viaje;
use App\Services\DisponibilidadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Viajes disponibles (sección pública: visitantes y viajeros).
 */
class ViajeController extends Controller
{
    public function __construct(private DisponibilidadService $disponibilidad) {}

    /** Listado de viajes a la venta, con filtros por origen, destino y fecha. */
    public function index(BuscarViajesRequest $request): View
    {
        $filtros = $request->validated();

        $viajes = $this->disponibilidad->viajesDisponibles($this->consultaFiltrada($filtros));
        $libres = $viajes->mapWithKeys(fn (Viaje $v) => [$v->id => $this->disponibilidad->libresTotales($v)]);

        return view('viajes.index', [
            'viajesPorDia' => $viajes->groupBy(fn (Viaje $v) => $v->salida->toDateString()),
            'cantidad' => $viajes->count(),
            'libres' => $libres,
            'filtros' => $filtros,
            'destinos' => Destino::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Búsqueda dinámica (AJAX). Recibe los mismos filtros que el listado, consulta con Eloquent
     * y responde JSON; el navegador actualiza los resultados sin recargar la página.
     * Si los parámetros no son válidos, BuscarViajesRequest responde 422 con los errores en JSON.
     */
    public function buscar(BuscarViajesRequest $request): JsonResponse
    {
        $limite = 30;
        $viajes = $this->disponibilidad->viajesDisponibles($this->consultaFiltrada($request->validated()));

        return response()->json([
            'total' => $viajes->count(),
            'limite' => $limite,
            'viajes' => $viajes->take($limite)->map(fn (Viaje $v) => [
                'id' => $v->id,
                'url' => route('viajes.show', $v),
                'origen' => $v->origen->nombre,
                'destino' => $v->destino->nombre,
                'dia' => $v->salida->toDateString(),
                'dia_texto' => Str::ucfirst($v->salida->isoFormat('dddd D [de] MMMM')),
                'salida' => $v->salida->isoFormat('ddd D/MM · HH:mm').' h',
                'llegada' => $v->llegada_estimada->isoFormat('ddd D/MM · HH:mm').' h',
                'duracion' => $v->duracionTexto(),
                'precio' => (float) $v->precio,
                'precio_texto' => Number::currency((float) $v->precio),
                'libres' => $this->disponibilidad->libresTotales($v),
            ])->values(),
        ]);
    }

    /** Detalle de un viaje: horarios, precio, colectivos asociados y butacas. */
    public function show(Viaje $viaje): View
    {
        $viaje->load(['origen', 'destino.portada']);

        return view('viajes.show', [
            'viaje' => $viaje,
            'resumen' => $this->disponibilidad->resumen($viaje),
            'disponible' => $this->disponibilidad->estaDisponible($viaje),
        ]);
    }

    /** Estado actual de las butacas de cada colectivo del viaje (JSON para el selector de butacas). */
    public function butacas(Viaje $viaje): JsonResponse
    {
        return response()->json([
            'disponible' => $this->disponibilidad->estaDisponible($viaje),
            'colectivos' => $this->disponibilidad->resumen($viaje)->map(fn (array $fila) => [
                'id' => $fila['colectivo']->id,
                'numero_interno' => $fila['colectivo']->numero_interno,
                'operativo' => $fila['colectivo']->esOperativo(),
                'vendidas' => $fila['vendidas']->values(),
                'reservadas' => $fila['reservadas']->values(),
                'libres' => $fila['libres'],
            ])->values(),
        ]);
    }

    /**
     * Aplica los filtros validados a una consulta de viajes con Eloquent.
     * La disponibilidad (butacas libres, colectivos operativos) la resuelve DisponibilidadService.
     */
    private function consultaFiltrada(array $filtros): Builder
    {
        return Viaje::query()
            ->when($filtros['origen_id'] ?? null, fn (Builder $q, $id) => $q->where('origen_id', $id))
            ->when($filtros['destino_id'] ?? null, fn (Builder $q, $id) => $q->where('destino_id', $id))
            ->when($filtros['fecha'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('salida', $fecha))
            ->when($filtros['q'] ?? null, function (Builder $q, string $texto) {
                $q->where(function (Builder $w) use ($texto) {
                    $w->whereHas('destino', fn (Builder $d) => $d->where('nombre', 'like', "%{$texto}%"))
                        ->orWhereHas('origen', fn (Builder $o) => $o->where('nombre', 'like', "%{$texto}%"));
                });
            });
    }
}
