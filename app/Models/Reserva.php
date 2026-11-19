<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Butaca retenida por una compra mientras su pago está pendiente. No es un pasaje.
 */
class Reserva extends Model
{
    protected $table = 'reservas';

    public $timestamps = false;

    protected $fillable = ['viaje_id', 'colectivo_id', 'nro_butaca', 'precio'];

    protected function casts(): array
    {
        return [
            'nro_butaca' => 'integer',
            'precio' => 'decimal:2',
        ];
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'viaje_id');
    }

    public function colectivo(): BelongsTo
    {
        return $this->belongsTo(Colectivo::class, 'colectivo_id');
    }
}
