<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoColectivo;
use App\Http\Controllers\Controller;
use App\Http\Requests\ColectivoRequest;
use App\Models\Colectivo;
use App\Services\ColectivoService;
use App\Services\DisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestión de la flota. Las reglas sobre butacas y bajas están en ColectivoService.
 */
class ColectivoController extends Controller
{
    public function __construct(
        private ColectivoService $colectivos,
        private DisponibilidadService $disponibilidad,
    ) {}

    public function index(): View
    {
        $colectivos = Colectivo::withCount(['viajes as viajes_programados_count' => fn ($q) => $q->programados()])
            ->orderBy('numero_interno')
            ->get();

        return view('admin.colectivos.index', compact('colectivos'));
    }

    public function create(): View
    {
        return view('admin.colectivos.create', ['colectivo' => new Colectivo(['estado' => EstadoColectivo::Operativo])]);
    }

    public function store(ColectivoRequest $request): RedirectResponse
    {
        $colectivo = $this->colectivos->crear($request->validated());

        return redirect()->route('admin.colectivos.show', $colectivo)
            ->with('exito', "Colectivo {$colectivo->numero_interno} creado con {$colectivo->capacidad} butacas.");
    }

    /** Ficha del colectivo: sus butacas y la ocupación en cada viaje programado. */
    public function show(Colectivo $colectivo): View
    {
        $colectivo->load('butacas');

        $viajes = $colectivo->viajes()->programados()->with(['origen', 'destino'])->orderBy('salida')->get();
        $this->disponibilidad->cargar($viajes);

        $ocupacion = $viajes->mapWithKeys(fn ($viaje) => [
            $viaje->id => $this->disponibilidad->butacasOcupadas($viaje, $colectivo)->count(),
        ]);

        return view('admin.colectivos.show', compact('colectivo', 'viajes', 'ocupacion'));
    }

    public function edit(Colectivo $colectivo): View
    {
        return view('admin.colectivos.edit', compact('colectivo'));
    }

    public function update(ColectivoRequest $request, Colectivo $colectivo): RedirectResponse
    {
        $this->colectivos->actualizar($colectivo, $request->validated());

        $redireccion = redirect()->route('admin.colectivos.show', $colectivo)
            ->with('exito', "Colectivo {$colectivo->numero_interno} actualizado.");

        // Un colectivo dado de baja deja de vender, pero los pasajes ya vendidos siguen existiendo.
        if (! $colectivo->esOperativo()) {
            $afectados = $colectivo->pasajes()->whereHas('viaje', fn ($q) => $q->programados())->count();

            if ($afectados > 0) {
                $redireccion->with('info', "Atención: el colectivo quedó dado de baja con {$afectados} pasajes vendidos en viajes programados. Ya no se venderán más butacas en él.");
            }
        }

        return $redireccion;
    }

    public function destroy(Colectivo $colectivo): RedirectResponse
    {
        $this->colectivos->eliminar($colectivo);

        return redirect()->route('admin.colectivos.index')->with('exito', "Colectivo {$colectivo->numero_interno} eliminado.");
    }
}
