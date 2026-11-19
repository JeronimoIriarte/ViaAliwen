<?php

namespace App\Policies;

use App\Models\Compra;
use App\Models\User;

/**
 * Un viajero sólo puede ver (y operar sobre) sus propias compras.
 */
class CompraPolicy
{
    public function view(User $user, Compra $compra): bool
    {
        return $user->viajero !== null && (int) $compra->viajero_id === $user->viajero->id;
    }
}
