<?php

namespace App\Services;

use App\Exceptions\CompraException;
use App\Models\Compra;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPSearchRequest;
use MercadoPago\Webhook\WebhookSignatureValidator;
use Throwable;

/**
 * Única clase que habla con Mercado Pago (librería oficial mercadopago/dx-php).
 * Devuelve arreglos simples para que el resto del sistema no dependa de las clases del SDK.
 */
class MercadoPagoService
{
    public function configurado(): bool
    {
        return filled(config('services.mercadopago.access_token'));
    }

    /**
     * Crea la preferencia de Checkout Pro de una compra: un ítem por butaca reservada.
     *
     * @return array{id: string, url: string}
     */
    public function crearPreferencia(Compra $compra): array
    {
        $this->autenticar();
        $compra->loadMissing(['reservas.viaje.origen', 'reservas.viaje.destino', 'reservas.colectivo']);

        $preferencia = [
            'items' => $compra->reservas->map(fn (Reserva $r) => [
                'id' => "viaje-{$r->viaje_id}-colectivo-{$r->colectivo->numero_interno}-butaca-{$r->nro_butaca}",
                'title' => "Pasaje {$r->viaje->origen->nombre} - {$r->viaje->destino->nombre}",
                'description' => 'Salida '.$r->viaje->salida->format('d/m/Y H:i')
                    ." · Colectivo {$r->colectivo->numero_interno} · Butaca {$r->nro_butaca}",
                'quantity' => 1,
                'currency_id' => 'ARS',
                'unit_price' => (float) $r->precio,
            ])->values()->all(),
            // Así identificamos la compra cuando Mercado Pago informe el pago.
            'external_reference' => $compra->referencia,
            'statement_descriptor' => 'VIAALIWEN',
            // La preferencia vence junto con la reserva de butacas: no se puede pagar una reserva vencida.
            'expires' => true,
            'expiration_date_from' => now()->format('Y-m-d\TH:i:s.vP'),
            'expiration_date_to' => $compra->expira_en->format('Y-m-d\TH:i:s.vP'),
            // Sin efectivo (Rapipago, Pago Fácil): esos pagos quedan pendientes días, más que la reserva.
            'payment_methods' => [
                'excluded_payment_types' => [['id' => 'ticket'], ['id' => 'atm']],
                'installments' => 1,
            ],
        ];

        // Mercado Pago no acepta localhost: la vuelta automática y el webhook sólo se envían con una URL pública.
        if ($url = config('services.mercadopago.url_publica')) {
            $preferencia['back_urls'] = [
                'success' => "{$url}/pagos/retorno",
                'pending' => "{$url}/pagos/retorno",
                'failure' => "{$url}/pagos/retorno",
            ];
            $preferencia['auto_return'] = 'approved';
            $preferencia['notification_url'] = "{$url}/webhooks/mercadopago";
        }

        try {
            $respuesta = (new PreferenceClient)->create($preferencia);
        } catch (Throwable $e) {
            $this->registrarError('No se pudo crear la preferencia', $e, ['compra' => $compra->referencia]);
            throw new CompraException('No pudimos conectarnos con Mercado Pago. Tus butacas no quedaron reservadas: probá de nuevo en unos minutos.');
        }

        return [
            'id' => $respuesta->id,
            'url' => $this->usarSandbox() && $respuesta->sandbox_init_point
                ? $respuesta->sandbox_init_point
                : $respuesta->init_point,
        ];
    }

    /** Consulta un pago a la API de Mercado Pago (no se confía en lo que diga el navegador). */
    public function obtenerPago(int $id): array
    {
        $this->autenticar();

        return $this->normalizar((new PaymentClient)->get($id));
    }

    /**
     * Busca los pagos asociados a una compra por su external_reference.
     *
     * @return array<int, array>
     */
    public function buscarPagos(string $referencia): array
    {
        $this->autenticar();

        $resultado = (new PaymentClient)->search(new MPSearchRequest(30, 0, [
            'external_reference' => $referencia,
            'sort' => 'date_created',
            'criteria' => 'desc',
        ]));

        return collect($resultado->results ?? [])->map(fn ($pago) => $this->normalizar($pago))->all();
    }

    /**
     * Valida la firma (x-signature) de una notificación con la clave secreta del panel de Mercado Pago.
     * Si no hay clave configurada se acepta: igual nunca se confía en el contenido de la notificación,
     * porque el pago se vuelve a consultar a la API.
     */
    public function firmaValida(Request $request): bool
    {
        $secreto = (string) config('services.mercadopago.webhook_secret');

        if ($secreto === '') {
            return true;
        }

        try {
            WebhookSignatureValidator::validate(
                $request->header('x-signature'),
                $request->header('x-request-id'),
                $request->query('data_id'), // PHP convierte "data.id" de la URL en "data_id"
                $secreto,
            );

            return true;
        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('Webhook de Mercado Pago con firma inválida', ['request_id' => $request->header('x-request-id')]);

            return false;
        }
    }

    /** Con credenciales de prueba (TEST-...) se usa el checkout de sandbox, salvo que se fuerce lo contrario. */
    private function usarSandbox(): bool
    {
        $valor = config('services.mercadopago.sandbox', 'auto');

        if (is_bool($valor)) {
            return $valor;
        }

        return str_starts_with((string) config('services.mercadopago.access_token'), 'TEST-');
    }

    private function autenticar(): void
    {
        if (! $this->configurado()) {
            throw new CompraException('Los pagos no están configurados: falta MERCADOPAGO_ACCESS_TOKEN en el archivo .env.');
        }

        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
    }

    /** Convierte un pago del SDK (Payment o resultado de búsqueda) en un arreglo con los datos que usamos. */
    private function normalizar(array|object $pago): array
    {
        $pago = (object) $pago; // los resultados de búsqueda pueden llegar como arreglos

        return [
            'id' => (int) $pago->id,
            'estado' => (string) $pago->status,
            'detalle' => $pago->status_detail ?? null,
            'referencia' => $pago->external_reference ?? null,
            'monto' => (float) ($pago->transaction_amount ?? 0),
            'moneda' => $pago->currency_id ?? null,
            'medio' => $pago->payment_method_id ?? null,
            'fecha_aprobacion' => $pago->date_approved ?? null,
        ];
    }

    private function registrarError(string $mensaje, Throwable $e, array $contexto = []): void
    {
        if ($e instanceof MPApiException) {
            $contexto['status'] = $e->getApiResponse()?->getStatusCode();
            $contexto['respuesta'] = $e->getApiResponse()?->getContent();
        }

        Log::error("Mercado Pago: {$mensaje}", $contexto + ['error' => $e->getMessage()]);
    }
}
