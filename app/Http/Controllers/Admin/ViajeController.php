<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViajeRequest;
use App\Models\Colectivo;
use App\Models\Destino;
use App\Models\Viaje;
use App\Services\DisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * CRUD de viajes (panel administrativo). La asignación de colectivos está en ViajeColectivoController.
 */
class ViajeController extends Controller
{
    public function __construct(private DisponibilidadService $disponibilidad) {}

    public function index(Request $request): View
    {
        $estado = $request->input('estado', 'programados');
        $destinoId = $request->integer('destino_id') ?: null;

        $consulta = Viaje::with(['origen', 'destino', 'creador'])->withCount('pasajes');

        match ($estado) {
            'realizados' => $consulta->realizados()->orderByDesc('salida'),
            'todos' => $consulta->orderByDesc('salida'),
            default => $consulta->programados()->orderBy('salida'),
        };

        $viajes = $consulta
            ->when($destinoId, fn ($q) => $q->where('destino_id', $destinoId))
            ->paginate(15)
            ->withQueryString();

        // Butacas libres de cada viaje de la página (una sola tanda de consultas gracias a loadMissing).
        $this->disponibilidad->cargar($viajes->getCollection());
        $libres = $viajes->getCollection()->mapWithKeys(fn (Viaje $v) => [$v->id => $this->disponibilidad->libresTotales($v)]);

        return view('admin.viajes.index', [
            'viajes' => $viajes,
            'libres' => $libres,
            'estado' => $estado,
            'destinoId' => $destinoId,
            'destinos' => Destino::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Viaje::class);

        return view('admin.viajes.create', [
            'viaje' => new Viaje,
            'destinos' => Destino::orderBy('nombre')->get(['id', 'nombre']),
            'colectivos' => Colectivo::operativos()->orderBy('numero_interno')->get(),
        ]);
    }

    public function store(ViajeRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $viaje = DB::transaction(function () use ($request, $datos) {
            // user_id = administrador autenticado (relación User hasMany Viaje).
            $viaje = $request->user()->viajesCargados()->create($datos);

            // attach(): registra cada colectivo en la tabla intermedia colectivo_viaje.
            $viaje->colectivos()->attach($datos['colectivos']);

            return $viaje;
        });

        return redirect()->route('admin.viajes.show', $viaje)->with('exito', 'Viaje creado correctamente.');
    }

    public function show(Viaje $viaje): View
    {
        $viaje->load(['origen', 'destino', 'creador']);

        return view('admin.viajes.show', [
            'viaje' => $viaje,
            'resumen' => $this->disponibilidad->resumen($viaje),
            'disponible' => $this->disponibilidad->estaDisponible($viaje),
            'pasajes' => $viaje->pasajes()->with(['viajero', 'colectivo'])->orderBy('colectivo_id')->orderBy('nro_butaca')->get(),
            'colectivosLibres' => Colectivo::operativos()
                ->whereNotIn('id', $viaje->colectivos->pluck('id'))
                ->orderBy('numero_interno')
                ->get(),
        ]);
    }

    public function edit(Viaje $viaje): View
    {
        Gate::authorize('update', $viaje);

        return view('admin.viajes.edit', [
            'viaje' => $viaje,
            'soloPrecio' => $viaje->tieneVentas(),
            'destinos' => Destino::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(ViajeRequest $request, Viaje $viaje): RedirectResponse
    {
        // validated() sólo trae los campos permitidos (si hay ventas, únicamente el precio).
        $viaje->update($request->validated());

        $mensaje = $request->soloPrecio()
            ? 'Precio actualizado. Los pasajes ya vendidos conservan el precio que se pagó.'
            : 'Viaje actualizado.';

        return redirect()->route('admin.viajes.show', $viaje)->with('exito', $mensaje);
    }

    public function destroy(Viaje $viaje): RedirectResponse
    {
        Gate::authorize('delete', $viaje); // creador, sin pasajes vendidos ni pagos en curso

        $viaje->delete(); // las filas de colectivo_viaje se borran en cascada

        return redirect()->route('admin.viajes.index')->with('exito', 'Viaje eliminado.');
    }
}
