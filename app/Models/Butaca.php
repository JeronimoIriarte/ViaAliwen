<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asiento físico de un colectivo. No guarda disponibilidad: una misma butaca puede
 * estar libre en un viaje y vendida en otro, por eso se calcula según el viaje.
 */
class Butaca extends Model
{
    protected $table = 'butacas';

    public $timestamps = false;

    protected $fillable = ['numero'];

    /** N:1 — Colectivo al que pertenece. */
    public function colectivo(): BelongsTo
    {
        return $this->belongsTo(Colectivo::class, 'colectivo_id');
    }
}
