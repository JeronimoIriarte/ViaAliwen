<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Destino extends Model
{
    protected $table = 'destinos';

    protected $fillable = ['nombre', 'descripcion', 'destacado'];

    protected function casts(): array
    {
        return [
            'destacado' => 'boolean',
        ];
    }

    /** 1:N — Viajes que llegan a este destino. */
    public function viajes(): HasMany
    {
        return $this->hasMany(Viaje::class, 'destino_id');
    }

    /** 1:N — Viajes que parten desde esta localidad (como origen). */
    public function viajesDesde(): HasMany
    {
        return $this->hasMany(Viaje::class, 'origen_id');
    }

    /** 1:N — Galería de fotografías, en el orden definido. */
    public function imagenes(): HasMany
    {
        return $this->hasMany(DestinoImagen::class, 'destino_id')->orderBy('orden');
    }

    /** Primera foto de la galería (la de menor "orden"), para tarjetas y portadas. */
    public function portada(): HasOne
    {
        return $this->hasOne(DestinoImagen::class, 'destino_id')->oldestOfMany('orden');
    }

    /** Pasajes vendidos hacia este destino (Destino -> Viaje -> Pasaje). Para estadísticas. */
    public function pasajes(): HasManyThrough
    {
        return $this->hasManyThrough(Pasaje::class, Viaje::class, 'destino_id', 'viaje_id');
    }

    /** Pasajes vendidos de viajes que salen desde esta localidad. Para estadísticas por origen. */
    public function pasajesDesde(): HasManyThrough
    {
        return $this->hasManyThrough(Pasaje::class, Viaje::class, 'origen_id', 'viaje_id');
    }

    public function scopeDestacados(Builder $query): Builder
    {
        return $query->where('destacado', true);
    }
}
