<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Services\CheckoutService;
use App\Services\ConfirmacionPagoService;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Historial de compras del viajero autenticado ("Mis compras").
 */
class CompraController extends Controller
{
    public function __construct(
        private CheckoutService $checkout,
        private MercadoPagoService $mercadoPago,
        private ConfirmacionPagoService $confirmacion,
    ) {}

    public function index(Request $request): View
    {
        $this->checkout->marcarVencidas();

        $compras = $request->user()->viajero->compras()
            ->with(['pasajes.viaje.origen', 'pasajes.viaje.destino', 'reservas.viaje.origen', 'reservas.viaje.destino'])
            ->latest()
            ->paginate(10);

        return view('compras.index', compact('compras'));
    }

    public function show(Compra $compra): View
    {
        Gate::authorize('view', $compra); // sólo el viajero dueño de la compra

        $compra = $this->actualizarEstado($compra);

        $compra->load([
            'pasajes.viaje.origen', 'pasajes.viaje.destino', 'pasajes.colectivo',
            'reservas.viaje.origen', 'reservas.viaje.destino', 'reservas.colectivo',
            'pagos' => fn ($q) => $q->latest(),
        ]);

        return view('compras.show', compact('compra'));
    }

    /**
     * Estado actual de la compra en JSON. Lo consulta con fetch la página de una compra pendiente
     * mientras el viajero paga en la otra pestaña: cuando cambia, la página se recarga sola.
     */
    public function estado(Compra $compra): JsonResponse
    {
        Gate::authorize('view', $compra);

        $compra = $this->actualizarEstado($compra);

        return response()->json([
            'estado' => $compra->estado->value,
            'etiqueta' => $compra->estado->etiqueta(),
            'pendiente' => $compra->estado === EstadoCompra::Pendiente,
        ]);
    }

    /**
     * Marca vencidas y, si la compra sigue pendiente (o venció hace poco), consulta a Mercado Pago
     * si ya hay un pago aprobado. Sirve cuando el viajero vuelve sin pasar por la URL de retorno.
     */
    private function actualizarEstado(Compra $compra): Compra
    {
        $this->checkout->marcarVencidas();
        $compra->refresh();

        if (in_array($compra->estado, [EstadoCompra::Pendiente, EstadoCompra::Vencida], true) && $this->mercadoPago->configurado()) {
            try {
                $compra = $this->confirmacion->verificarCompra($compra);
            } catch (Throwable $e) {
                Log::warning('No se pudo verificar la compra', ['compra' => $compra->referencia, 'error' => $e->getMessage()]);
            }
        }

        return $compra;
    }
}
