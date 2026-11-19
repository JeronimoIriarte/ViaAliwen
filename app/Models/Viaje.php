<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Viaje extends Model
{
    protected $table = 'viajes';

    // user_id no es asignable: se completa al crear con $admin->viajesCargados()->create().
    protected $fillable = ['destino_id', 'origen_id', 'precio', 'salida', 'llegada_estimada'];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'salida' => 'datetime',
            'llegada_estimada' => 'datetime',
        ];
    }

    /** N:1 — Destino del viaje. */
    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'destino_id');
    }

    /** N:1 — Localidad de origen (también es un registro de destinos). */
    public function origen(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'origen_id');
    }

    /** N:1 — Administrador que cargó el viaje. */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** N:M — Colectivos asignados al viaje (tabla intermedia colectivo_viaje = SALE). */
    public function colectivos(): BelongsToMany
    {
        return $this->belongsToMany(Colectivo::class, 'colectivo_viaje', 'viaje_id', 'colectivo_id')
            ->withTimestamps();
    }

    /** 1:N — Pasajes vendidos de este viaje. */
    public function pasajes(): HasMany
    {
        return $this->hasMany(Pasaje::class, 'viaje_id');
    }

    /** 1:N — Todas las reservas (de compras en cualquier estado). */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'viaje_id');
    }

    /** Reservas que hoy retienen butacas: compra pendiente y todavía no vencida. */
    public function reservasActivas(): HasMany
    {
        return $this->reservas()->whereHas('compra', fn (Builder $q) => $q->vigentes());
    }

    /** Viajes que todavía no salieron. */
    public function scopeProgramados(Builder $query): Builder
    {
        return $query->where('salida', '>', now());
    }

    /** Viajes realizados: ya pasó la llegada estimada (no depende de los pasajes vendidos). */
    public function scopeRealizados(Builder $query): Builder
    {
        return $query->where('llegada_estimada', '<=', now());
    }

    public function estado(): string
    {
        return match (true) {
            $this->salida->isFuture() => 'Programado',
            $this->llegada_estimada->isFuture() => 'En viaje',
            default => 'Realizado',
        };
    }

    public function tieneVentas(): bool
    {
        return $this->pasajes()->exists();
    }

    /** Duración aproximada para mostrar, p. ej. "6 h 30 min" (sólo se muestra; la llegada se carga a mano). */
    public function duracionTexto(): string
    {
        $diferencia = $this->salida->diff($this->llegada_estimada);
        $horas = $diferencia->days * 24 + $diferencia->h;

        return $horas.' h'.($diferencia->i ? " {$diferencia->i} min" : '');
    }
}
