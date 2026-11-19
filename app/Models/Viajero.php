<?php

namespace App\Models;

use Database\Factories\ViajeroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Viajero extends Model
{
    /** @use HasFactory<ViajeroFactory> */
    use HasFactory;

    protected $table = 'viajeros';

    // user_id no es asignable: el viajero se crea siempre a través de $user->viajero()->create().
    protected $fillable = ['nombre', 'apellido', 'dni', 'fecha_nacimiento'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    /** 1:1 inversa — Cuenta de usuario (email y contraseña). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 1:N — Compras realizadas. */
    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'viajero_id');
    }

    /** 1:N — Pasajes de los que es titular. */
    public function pasajes(): HasMany
    {
        return $this->hasMany(Pasaje::class, 'viajero_id');
    }

    public function nombreCompleto(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
