<?php

namespace App\Services;

use App\Enums\EstadoCompra;
use App\Models\Compra;
use App\Models\Pasaje;
use App\Models\Reserva;
use App\Models\Viaje;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Confirma compras a partir de los datos REALES del pago, obtenidos de la API de Mercado Pago.
 * Se invoca desde la vuelta del checkout, desde el webhook y desde el botón "Verificar pago".
 *
 * Garantías:
 *  - Los pasajes se emiten sólo si Mercado Pago informa el pago como "approved" y el monto coincide.
 *  - Es idempotente: procesar el mismo pago varias veces (notificaciones repetidas) no duplica pasajes.
 *  - Antes de emitir se vuelve a bloquear el viaje y a comprobar que las butacas sigan libres.
 */
class ConfirmacionPagoService
{
    public function __construct(private MercadoPagoService $mercadoPago) {}

    /** Procesa un pago por su id de Mercado Pago. */
    public function procesarPago(int $paymentId): ?Compra
    {
        return $this->registrar($this->mercadoPago->obtenerPago($paymentId));
    }

    /** Busca en Mercado Pago los pagos de una compra y los procesa (útil si el viajero no volvió del checkout). */
    public function verificarCompra(Compra $compra): Compra
    {
        foreach ($this->mercadoPago->buscarPagos($compra->referencia) as $pago) {
            $this->registrar($pago);
        }

        return $compra->fresh();
    }

    /**
     * Registra el intento de pago y, si corresponde, emite los pasajes.
     *
     * @param  array{id: int, estado: string, detalle: ?string, referencia: ?string, monto: float, moneda: ?string, medio: ?string, fecha_aprobacion: ?string}  $pago
     */
    public function registrar(array $pago): ?Compra
    {
        $compra = $pago['referencia'] ? Compra::where('referencia', $pago['referencia'])->first() : null;

        if (! $compra) {
            Log::warning('Pago de Mercado Pago sin compra asociada', ['payment_id' => $pago['id'], 'referencia' => $pago['referencia']]);

            return null;
        }

        try {
            return DB::transaction(fn () => $this->confirmar($compra->id, $pago));
        } catch (UniqueConstraintViolationException $e) {
            // Último resguardo: el índice único de pasajes impidió vender dos veces la misma butaca.
            Log::error('Butaca vendida dos veces evitada por la base de datos', ['compra' => $compra->referencia]);
            $compra->update(['estado' => EstadoCompra::Conflicto]);

            return $compra->fresh();
        }
    }

    private function confirmar(int $compraId, array $pago): Compra
    {
        // Bloquea la compra: dos notificaciones simultáneas del mismo pago se procesan de a una.
        $compra = Compra::whereKey($compraId)->lockForUpdate()->firstOrFail();

        // Cada intento queda registrado una sola vez (mp_payment_id es único).
        $compra->pagos()->updateOrCreate(
            ['mp_payment_id' => $pago['id']],
            [
                'estado' => $pago['estado'],
                'estado_detalle' => $pago['detalle'],
                'monto' => $pago['monto'],
                'medio_pago' => $pago['medio'],
                'fecha_aprobacion' => $pago['fecha_aprobacion'] ? Carbon::parse($pago['fecha_aprobacion']) : null,
            ],
        );

        // Rechazado, pendiente o cancelado: no se emite nada (la compra sigue pendiente hasta que venza).
        if ($pago['estado'] !== 'approved') {
            return $compra;
        }

        // Idempotencia: si ya se emitieron los pasajes (o quedó en conflicto), no se hace nada más.
        if (in_array($compra->estado, [EstadoCompra::Aprobada, EstadoCompra::Conflicto], true)) {
            return $compra;
        }

        if (abs($pago['monto'] - (float) $compra->total) > 0.009 || $pago['moneda'] !== 'ARS') {
            Log::error('El monto pagado no coincide con la compra', ['compra' => $compra->referencia, 'pago' => $pago]);

            return tap($compra)->update(['estado' => EstadoCompra::Conflicto]);
        }

        $reservas = $compra->reservas()->get();

        Viaje::whereIn('id', $reservas->pluck('viaje_id')->unique())->orderBy('id')->lockForUpdate()->get();

        foreach ($reservas as $reserva) {
            if (! $this->butacaSigueDisponible($compra, $reserva)) {
                // Caso raro: el pago se aprobó después de vencer la reserva y otra persona tomó la butaca.
                Log::warning('Pago aprobado con una butaca ya tomada: requiere reintegro', ['compra' => $compra->referencia]);

                return tap($compra)->update(['estado' => EstadoCompra::Conflicto]);
            }
        }

        // Recién ahora se generan los pasajes, uno por butaca, con el precio que se pagó.
        foreach ($reservas as $reserva) {
            $compra->pasajes()->create([
                'codigo' => Pasaje::generarCodigo(),
                'viajero_id' => $compra->viajero_id,
                'viaje_id' => $reserva->viaje_id,
                'colectivo_id' => $reserva->colectivo_id,
                'nro_butaca' => $reserva->nro_butaca,
                'precio' => $reserva->precio,
            ]);
        }

        return tap($compra)->update(['estado' => EstadoCompra::Aprobada, 'pagada_en' => now()]);
    }

    /** La butaca no tiene pasaje y no la retiene otra compra vigente. */
    private function butacaSigueDisponible(Compra $compra, Reserva $reserva): bool
    {
        $vendida = Pasaje::where('viaje_id', $reserva->viaje_id)
            ->where('colectivo_id', $reserva->colectivo_id)
            ->where('nro_butaca', $reserva->nro_butaca)
            ->exists();

        $retenidaPorOtra = Reserva::where('viaje_id', $reserva->viaje_id)
            ->where('colectivo_id', $reserva->colectivo_id)
            ->where('nro_butaca', $reserva->nro_butaca)
            ->where('compra_id', '!=', $compra->id)
            ->whereHas('compra', fn ($q) => $q->vigentes())
            ->exists();

        return ! $vendida && ! $retenidaPorOtra;
    }
}
