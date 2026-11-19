<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Viajero;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta de viajeros (sólo lectura): el administrador tampoco modifica datos personales ajenos.
 */
class ViajeroController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->input('q'));

        $viajeros = Viajero::with('user')
            ->withCount('pasajes')
            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                $consulta->where(function ($q) use ($busqueda) {
                    $q->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('apellido', 'like', "%{$busqueda}%")
                        ->orWhere('dni', 'like', "%{$busqueda}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$busqueda}%"));
                });
            })
            ->orderBy('apellido')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('admin.viajeros.index', compact('viajeros', 'busqueda'));
    }

    public function show(Viajero $viajero): View
    {
        $viajero->load([
            'user',
            'compras' => fn ($q) => $q->latest()->with(['pasajes.viaje.origen', 'pasajes.viaje.destino', 'pasajes.colectivo']),
        ]);

        return view('admin.viajeros.show', compact('viajero'));
    }
}
