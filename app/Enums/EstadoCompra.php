<?php

namespace App\Enums;

/**
 * Ciclo de vida de una compra:
 *  pendiente -> aprobada  (pago verificado con Mercado Pago: se emiten los pasajes)
 *  pendiente -> vencida   (pasó el tiempo de reserva sin pago aprobado)
 *  pendiente -> cancelada (el viajero desistió)
 *  pendiente -> conflicto (pago aprobado pero alguna butaca ya no estaba libre: requiere reintegro)
 */
enum EstadoCompra: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Vencida = 'vencida';
    case Cancelada = 'cancelada';
    case Conflicto = 'conflicto';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pago pendiente',
            self::Aprobada => 'Aprobada',
            self::Vencida => 'Vencida',
            self::Cancelada => 'Cancelada',
            self::Conflicto => 'Requiere reintegro',
        };
    }

    /** Clase de color de Bootstrap para mostrar el estado. */
    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'warning',
            self::Aprobada => 'success',
            self::Vencida, self::Cancelada => 'secondary',
            self::Conflicto => 'danger',
        };
    }
}
