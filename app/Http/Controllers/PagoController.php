<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCompra;
use App\Exceptions\CompraException;
use App\Models\Compra;
use App\Services\ConfirmacionPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vuelta desde el checkout y acciones del viajero sobre una compra pendiente.
 */
class PagoController extends Controller
{
    public function __construct(private ConfirmacionPagoService $confirmacion) {}

    /**
     * Mercado Pago redirige acá al terminar (sólo si hay una URL pública configurada).
     * Los parámetros de la URL NO se usan para decidir: el pago se consulta a la API.
     */
    public function retorno(Request $request): RedirectResponse
    {
        $paymentId = $request->query('payment_id') ?? $request->query('collection_id');
        $compra = null;

        if (is_numeric($paymentId)) {
            try {
                $compra = $this->confirmacion->procesarPago((int) $paymentId);
            } catch (Throwable $e) {
                Log::error('No se pudo verificar el pago al volver del checkout', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
            }
        }

        $compra ??= Compra::where('referencia', (string) $request->query('external_reference'))->first();

        if (! $compra) {
            return redirect()->route('compras.index')->with('info', 'Volviste de Mercado Pago. Revisá el estado de tu compra acá.');
        }

        return redirect()->route('compras.show', $compra)->with(...$this->mensajeSegunEstado($compra));
    }

    /** Botón "Verificar pago": consulta a Mercado Pago los pagos de la compra. */
    public function verificar(Compra $compra): RedirectResponse
    {
        Gate::authorize('view', $compra);

        try {
            $compra = $this->confirmacion->verificarCompra($compra);
        } catch (CompraException $e) {
            return back()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            Log::error('Error al verificar un pago', ['compra' => $compra->referencia, 'error' => $e->getMessage()]);

            return back()->with('error', 'No pudimos consultar a Mercado Pago en este momento. Probá de nuevo en unos minutos.');
        }

        return redirect()->route('compras.show', $compra)->with(...$this->mensajeSegunEstado($compra));
    }

    /** Cancela una compra pendiente y libera sus butacas (antes verifica que no se haya pagado). */
    public function cancelar(Compra $compra): RedirectResponse
    {
        Gate::authorize('view', $compra);

        if ($compra->estado !== EstadoCompra::Pendiente) {
            return back()->with('error', 'Sólo se puede cancelar una compra con el pago pendiente.');
        }

        try {
            $compra = $this->confirmacion->verificarCompra($compra);
        } catch (Throwable $e) {
            return back()->with('error', 'No pudimos confirmar con Mercado Pago que la compra no esté pagada. Probá de nuevo en unos minutos.');
        }

        if ($compra->estado !== EstadoCompra::Pendiente) {
            return redirect()->route('compras.show', $compra)->with(...$this->mensajeSegunEstado($compra));
        }

        $compra->update(['estado' => EstadoCompra::Cancelada]);

        return redirect()->route('compras.show', $compra)->with('exito', 'Cancelaste la compra y liberamos las butacas.');
    }

    /** @return array{0: string, 1: string} clave y texto del mensaje flash */
    private function mensajeSegunEstado(Compra $compra): array
    {
        return match ($compra->estado) {
            EstadoCompra::Aprobada => ['exito', '¡Pago aprobado! Tus pasajes ya están emitidos.'],
            EstadoCompra::Pendiente => ['info', 'Todavía no recibimos un pago aprobado para esta compra. Si pagaste, esperá unos segundos y tocá "Verificar pago".'],
            EstadoCompra::Conflicto => ['error', 'El pago se aprobó, pero alguna butaca ya no estaba disponible. Vamos a contactarte para el reintegro.'],
            default => ['info', "La compra está {$compra->estado->etiqueta()}."],
        };
    }
}
