<?php

namespace App\Http\Controllers;

use App\Models\Destino;
use App\Models\Viaje;
use App\Services\DisponibilidadService;
use Illuminate\View\View;

/**
 * Página de inicio: buscador, destinos destacados y próximos viajes a la venta.
 */
class HomeController extends Controller
{
    public function __invoke(DisponibilidadService $disponibilidad): View
    {
        $proximos = $disponibilidad->viajesDisponibles(limite: 6);

        return view('home', [
            'destacados' => Destino::destacados()->with('portada')->orderBy('nombre')->get(),
            'destinos' => Destino::orderBy('nombre')->get(['id', 'nombre']),
            'proximos' => $proximos,
            'libres' => $proximos->mapWithKeys(fn (Viaje $v) => [$v->id => $disponibilidad->libresTotales($v)]),
        ]);
    }
}
