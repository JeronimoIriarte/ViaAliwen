<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Intento de pago informado por Mercado Pago. estado guarda el "status" de Mercado Pago
 * (approved, rejected, pending, in_process, cancelled...).
 */
class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = ['mp_payment_id', 'estado', 'estado_detalle', 'monto', 'medio_pago', 'fecha_aprobacion'];

    protected function casts(): array
    {
        return [
            'mp_payment_id' => 'integer',
            'monto' => 'decimal:2',
            'fecha_aprobacion' => 'datetime',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function aprobado(): bool
    {
        return $this->estado === 'approved';
    }
}
