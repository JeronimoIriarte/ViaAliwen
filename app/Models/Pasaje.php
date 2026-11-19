<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Pasaje emitido. Se crea únicamente cuando el pago de la compra fue verificado como aprobado.
 * created_at es la fecha de emisión; precio es lo que efectivamente se pagó.
 */
class Pasaje extends Model
{
    protected $table = 'pasajes';

    // compra_id se completa con $compra->pasajes()->create().
    protected $fillable = ['codigo', 'viajero_id', 'viaje_id', 'colectivo_id', 'nro_butaca', 'precio'];

    protected function casts(): array
    {
        return [
            'nro_butaca' => 'integer',
            'precio' => 'decimal:2',
        ];
    }

    /** N:1 — Compra en la que se emitió. */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    /** N:1 — Viajero titular del pasaje. */
    public function viajero(): BelongsTo
    {
        return $this->belongsTo(Viajero::class, 'viajero_id');
    }

    /** N:1 — Viaje comprado. */
    public function viaje(): BelongsTo
    {
        return $this->belongsTo(Viaje::class, 'viaje_id');
    }

    /** N:1 — Colectivo en el que viaja. */
    public function colectivo(): BelongsTo
    {
        return $this->belongsTo(Colectivo::class, 'colectivo_id');
    }

    /** Código legible y difícil de adivinar, p. ej. VA-7K3P9Q2M. */
    public static function generarCodigo(): string
    {
        do {
            $codigo = 'VA-'.Str::upper(Str::random(8));
        } while (static::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
