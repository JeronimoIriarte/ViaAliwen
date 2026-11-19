<?php

namespace App\Enums;

/**
 * Perfiles de usuario autenticado. El visitante no tiene rol: es quien no inició sesión.
 */
enum Rol: string
{
    case Admin = 'admin';
    case Viajero = 'viajero';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Admin => 'Administrador-vendedor',
            self::Viajero => 'Viajero',
        };
    }
}
