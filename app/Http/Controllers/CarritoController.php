<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgregarAlCarritoRequest;
use App\Services\CarritoService;
use App\Services\MercadoPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Carrito del viajero: ver, agregar y quitar butacas.
 */
class CarritoController extends Controller
{
    public function __construct(private CarritoService $carrito) {}

    public function index(Request $request, MercadoPagoService $mercadoPago): View
    {
        $detalle = $this->carrito->detalle($request->user()->viajero);

        return view('carrito.index', [
            'detalle' => $detalle,
            'total' => $detalle->whereNull('error')->sum('precio'),
            'hayErrores' => $detalle->whereNotNull('error')->isNotEmpty(),
            'pagosConfigurados' => $mercadoPago->configurado(),
            'minutosReserva' => config('services.mercadopago.reserva_minutos'),
        ]);
    }

    public function store(AgregarAlCarritoRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $this->carrito->agregar($request->user()->viajero, $datos['viaje_id'], $datos['colectivo_id'], $datos['nro_butaca']);

        return redirect()->route('carrito.index')
            ->with('exito', "Agregaste la butaca {$datos['nro_butaca']} a tu carrito.");
    }

    public function destroy(string $clave): RedirectResponse
    {
        $this->carrito->quitar($clave);

        return redirect()->route('carrito.index')->with('exito', 'Quitaste el viaje de tu carrito.');
    }
}
