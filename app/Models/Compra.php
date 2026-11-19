<?php

namespace App\Models;

use App\Enums\EstadoCompra;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    protected $table = 'compras';

    // viajero_id se completa con $viajero->compras()->create().
    protected $fillable = ['referencia', 'total', 'estado', 'mp_preference_id', 'mp_checkout_url', 'expira_en', 'pagada_en'];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'estado' => EstadoCompra::class,
            'expira_en' => 'datetime',
            'pagada_en' => 'datetime',
        ];
    }

    /** N:1 — Viajero que compra. */
    public function viajero(): BelongsTo
    {
        return $this->belongsTo(Viajero::class, 'viajero_id');
    }

    /** 1:N — Butacas retenidas por esta compra mientras se paga. */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'compra_id');
    }

    /** 1:N — Intentos de pago informados por Mercado Pago. */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'compra_id');
    }

    /** 1:N — Pasajes emitidos (sólo existen si la compra fue aprobada). */
    public function pasajes(): HasMany
    {
        return $this->hasMany(Pasaje::class, 'compra_id');
    }

    /** Compras pendientes cuya reserva sigue vigente: son las que retienen butacas. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', EstadoCompra::Pendiente)->where('expira_en', '>', now());
    }

    /** Compras pendientes cuyo plazo ya venció pero todavía no se marcaron como vencidas. */
    public function scopeVencidasSinMarcar(Builder $query): Builder
    {
        return $query->where('estado', EstadoCompra::Pendiente)->where('expira_en', '<=', now());
    }
}
