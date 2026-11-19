<?php

namespace App\Services;

use App\Enums\EstadoCompra;
use App\Exceptions\CompraException;
use App\Models\Compra;
use App\Models\Viaje;
use App\Models\Viajero;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Inicio del pago: revalida el carrito, reserva las butacas y crea la preferencia en Mercado Pago.
 */
class CheckoutService
{
    public function __construct(
        private CarritoService $carrito,
        private MercadoPagoService $mercadoPago,
    ) {}

    /** Devuelve la compra creada (pendiente) con la URL del checkout de Mercado Pago. */
    public function iniciar(Viajero $viajero): Compra
    {
        $items = $this->carrito->items();

        if ($items === []) {
            throw new CompraException('Tu carrito está vacío.');
        }

        if (! $this->mercadoPago->configurado()) {
            throw new CompraException('Los pagos con Mercado Pago todavía no están configurados.');
        }

        $this->marcarVencidas();

        if ($viajero->compras()->vigentes()->exists()) {
            throw new CompraException('Tenés una compra con el pago en curso. Terminá de pagarla o cancelala desde "Mis compras" antes de iniciar otra.');
        }

        $compra = DB::transaction(function () use ($viajero, $items) {
            // Bloqueo pesimista (SELECT ... FOR UPDATE) de los viajes involucrados: si dos personas
            // pagan butacas del mismo viaje al mismo tiempo, la segunda espera a que termine la primera
            // y recién ahí verifica la disponibilidad (ya ve la reserva de la otra). El orden evita bloqueos cruzados.
            $viajes = Viaje::whereIn('id', collect($items)->pluck('viaje_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // La disponibilidad se vuelve a comprobar ahora, no sólo cuando se agregó al carrito.
            $errores = $this->carrito->validar($viajero, $items);
            if ($errores !== []) {
                throw new CompraException('Algunas butacas de tu carrito ya no están disponibles: '.implode(' ', array_unique($errores)));
            }

            // Precios congelados al momento de iniciar el pago (es lo que se cobra en Mercado Pago).
            $reservas = collect($items)->map(fn (array $item) => [
                'viaje_id' => $item['viaje_id'],
                'colectivo_id' => $item['colectivo_id'],
                'nro_butaca' => $item['nro_butaca'],
                'precio' => $viajes[$item['viaje_id']]->precio,
            ])->values();

            $compra = $viajero->compras()->create([
                'referencia' => (string) Str::uuid(),
                'total' => $reservas->sum(fn ($r) => (float) $r['precio']),
                'estado' => EstadoCompra::Pendiente,
                'expira_en' => now()->addMinutes(config('services.mercadopago.reserva_minutos')),
            ]);

            $compra->reservas()->createMany($reservas->all());

            return $compra;
        });

        // La llamada a Mercado Pago va fuera de la transacción para no mantener los bloqueos durante una petición HTTP.
        try {
            $preferencia = $this->mercadoPago->crearPreferencia($compra);
        } catch (CompraException $e) {
            $compra->update(['estado' => EstadoCompra::Cancelada]); // libera las butacas
            throw $e;
        }

        $compra->update(['mp_preference_id' => $preferencia['id'], 'mp_checkout_url' => $preferencia['url']]);
        $this->carrito->vaciar();

        return $compra;
    }

    /** Las compras pendientes cuyo plazo terminó pasan a "vencida" (sus reservas dejan de retener butacas). */
    public function marcarVencidas(): int
    {
        return Compra::vencidasSinMarcar()->update(['estado' => EstadoCompra::Vencida]);
    }
}
