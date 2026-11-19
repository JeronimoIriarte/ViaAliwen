<?php

namespace App\Policies;

use App\Models\Pasaje;
use App\Models\User;

/**
 * Impide descargar pasajes ajenos: sólo el viajero titular obtiene el PDF.
 */
class PasajePolicy
{
    public function download(User $user, Pasaje $pasaje): bool
    {
        return $user->viajero !== null && (int) $pasaje->viajero_id === $user->viajero->id;
    }
}
