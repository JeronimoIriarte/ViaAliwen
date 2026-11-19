<?php

namespace App\Http\Controllers;

use App\Services\ConfirmacionPagoService;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notificaciones (webhooks) de Mercado Pago. Requiere una URL pública: Mercado Pago no puede
 * llamar a localhost. Está excluida de la protección CSRF porque la envía un servidor externo.
 */
class WebhookMercadoPagoController extends Controller
{
    public function __invoke(Request $request, MercadoPagoService $mercadoPago, ConfirmacionPagoService $confirmacion): Response
    {
        if (! $mercadoPago->firmaValida($request)) {
            return response('Firma inválida', 401);
        }

        $tipo = $request->input('type', $request->query('topic'));
        $paymentId = $request->query('data_id') ?? $request->input('data.id') ?? $request->query('id');

        // Sólo interesan las notificaciones de pagos; el resto se confirma sin procesar.
        if ($tipo !== 'payment' || ! is_numeric($paymentId)) {
            return response('OK', 200);
        }

        try {
            // Se consulta el pago a la API: el contenido de la notificación nunca se toma como verdad.
            $confirmacion->procesarPago((int) $paymentId);
        } catch (Throwable $e) {
            Log::error('Error procesando webhook de Mercado Pago', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);

            return response('Error', 500); // Mercado Pago reintentará más tarde
        }

        return response('OK', 200);
    }
}
