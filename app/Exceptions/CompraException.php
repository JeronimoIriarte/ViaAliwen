<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio durante una compra (butaca ocupada, Mercado Pago no disponible, etc.).
 * El mensaje está pensado para mostrárselo al viajero.
 */
class CompraException extends RuntimeException {}
