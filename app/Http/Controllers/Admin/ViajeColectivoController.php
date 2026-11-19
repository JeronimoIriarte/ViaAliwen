<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AsignarColectivoRequest;
use App\Models\Colectivo;
use App\Models\Viaje;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Asociación y desasociación de colectivos en un viaje (relación N:M con attach / detach).
 */
class ViajeColectivoController extends Controller
{
    public function store(AsignarColectivoRequest $request, Viaje $viaje): RedirectResponse
    {
        $colectivo = Colectivo::findOrFail($request->validated('colectivo_id'));

        $viaje->colectivos()->attach($colectivo->id);

        return redirect()->route('admin.viajes.show', $viaje)
            ->with('exito', "Colectivo {$colectivo->numero_interno} asignado al viaje: suma {$colectivo->capacidad} butacas.");
    }

    public function destroy(Viaje $viaje, Colectivo $colectivo): RedirectResponse
    {
        Gate::authorize('update', $viaje);

        abort_unless($viaje->colectivos()->whereKey($colectivo->id)->exists(), 404);

        if ($viaje->pasajes()->where('colectivo_id', $colectivo->id)->exists()) {
            return back()->with('error', "No se puede quitar el colectivo {$colectivo->numero_interno}: ya tiene pasajes vendidos en este viaje.");
        }

        if ($viaje->reservasActivas()->where('colectivo_id', $colectivo->id)->exists()) {
            return back()->with('error', "No se puede quitar el colectivo {$colectivo->numero_interno}: hay butacas reservadas con un pago en curso.");
        }

        $viaje->colectivos()->detach($colectivo->id);

        $mensaje = "Colectivo {$colectivo->numero_interno} quitado del viaje.";
        if ($viaje->colectivos()->doesntExist()) {
            $mensaje .= ' El viaje quedó sin colectivos: no se mostrará como disponible hasta que asignes uno.';
        }

        return redirect()->route('admin.viajes.show', $viaje)->with('exito', $mensaje);
    }
}
