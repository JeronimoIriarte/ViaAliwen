<?php

namespace App\Enums;

/**
 * Un colectivo dado de BAJA no puede usarse para vender pasajes.
 */
enum EstadoColectivo: string
{
    case Operativo = 'operativo';
    case Baja = 'baja';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Operativo => 'Operativo',
            self::Baja => 'Dado de baja',
        };
    }
}
