<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Viaje;
use App\Services\DisponibilidadService;
use Illuminate\View\View;

/**
 * Destinos y galerías (sección pública).
 */
class DestinoController extends Controller
{
    public function __construct(private DisponibilidadService $disponibilidad) {}

    public function index(): View
    {
        $destinos = Destino::with('portada')->orderByDesc('destacado')->orderBy('nombre')->get();

        // Cantidad de viajes a la venta hacia cada destino.
        $viajesPorDestino = $this->disponibilidad->viajesDisponibles()->countBy('destino_id');

        return view('destinos.index', compact('destinos', 'viajesPorDestino'));
    }

    public function show(Destino $destino): View
    {
        $destino->load('imagenes');

        $proximos = $this->disponibilidad->viajesDisponibles(Viaje::where('destino_id', $destino->id), 6);
        $libres = $proximos->mapWithKeys(fn (Viaje $v) => [$v->id => $this->disponibilidad->libresTotales($v)]);

        return view('destinos.show', compact('destino', 'proximos', 'libres'));
    }
}
