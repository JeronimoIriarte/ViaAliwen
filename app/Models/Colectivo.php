<?php

namespace App\Models;

use App\Enums\EstadoColectivo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colectivo extends Model
{
    protected $table = 'colectivos';

    protected $fillable = ['numero_interno', 'patente', 'capacidad', 'estado'];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'estado' => EstadoColectivo::class,
        ];
    }

    /** N:M — Viajes en los que sale este colectivo (tabla intermedia colectivo_viaje). */
    public function viajes(): BelongsToMany
    {
        return $this->belongsToMany(Viaje::class, 'colectivo_viaje', 'colectivo_id', 'viaje_id')
            ->withTimestamps();
    }

    /** 1:N — Butacas propias del colectivo, numeradas 1..capacidad. */
    public function butacas(): HasMany
    {
        return $this->hasMany(Butaca::class, 'colectivo_id')->orderBy('numero');
    }

    /** 1:N — Pasajes vendidos en este colectivo (en cualquiera de sus viajes). */
    public function pasajes(): HasMany
    {
        return $this->hasMany(Pasaje::class, 'colectivo_id');
    }

    public function scopeOperativos(Builder $query): Builder
    {
        return $query->where('estado', EstadoColectivo::Operativo);
    }

    public function esOperativo(): bool
    {
        return $this->estado === EstadoColectivo::Operativo;
    }
}
