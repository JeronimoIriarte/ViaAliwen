<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DestinoImagen extends Model
{
    protected $table = 'destino_imagenes';

    protected $fillable = ['ruta', 'epigrafe', 'credito', 'orden'];

    /** N:1 — Destino al que pertenece la foto. */
    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class, 'destino_id');
    }

    /** URL pública de la imagen (disco "public", requiere php artisan storage:link). */
    public function url(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }
}
