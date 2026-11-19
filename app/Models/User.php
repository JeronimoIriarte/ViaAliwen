<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario autenticable (credenciales y rol).
 * El rol NO es asignable masivamente: se fija explícitamente en el código,
 * así nadie puede registrarse como administrador enviando un campo extra.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = ['email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    /** 1:1 — Datos personales del viajero (los administradores no tienen). */
    public function viajero(): HasOne
    {
        return $this->hasOne(Viajero::class, 'user_id');
    }

    /** 1:N — Viajes cargados por este administrador. */
    public function viajesCargados(): HasMany
    {
        return $this->hasMany(Viaje::class, 'user_id');
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    public function esViajero(): bool
    {
        return $this->rol === Rol::Viajero;
    }

    /** Nombre para mostrar en la barra de navegación. */
    public function nombreVisible(): string
    {
        return $this->viajero?->nombre ?? $this->email;
    }
}
