<?php

namespace App\Http\Controllers;

use App\Exceptions\CompraException;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Inicio del pago: reserva las butacas del carrito y prepara el checkout de Mercado Pago.
 *
 * El viajero queda en la página de su compra; desde ahí abre el checkout en una pestaña nueva.
 * Así nunca pierde el sitio, y esa página se actualiza sola cuando el pago se aprueba.
 */
class CheckoutController extends Controller
{
    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        try {
            $compra = $checkout->iniciar($request->user()->viajero);
        } catch (CompraException $e) {
            return redirect()->route('carrito.index')->with('error', $e->getMessage());
        }

        return redirect()->route('compras.show', $compra)
            ->with('info', 'Reservamos tus butacas. Tocá "Pagar con Mercado Pago": el pago se abre en una pestaña nueva y esta página se actualiza sola cuando se aprueba.');
    }
}
