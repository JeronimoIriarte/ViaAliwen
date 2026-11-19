<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Viaje;
use Illuminate\Auth\Access\Response;

/**
 * Reglas de autorización sobre viajes. Laravel la descubre sola por convención de nombres
 * (App\Models\Viaje -> App\Policies\ViajePolicy). Se usa con Gate::authorize('update', $viaje).
 */
class ViajePolicy
{
    public function create(User $user): bool
    {
        return $user->esAdmin();
    }

    /** Un administrador sólo modifica los viajes que cargó él. */
    public function update(User $user, Viaje $viaje): Response
    {
        if (! $user->esAdmin()) {
            return Response::deny('Sólo los administradores pueden gestionar viajes.');
        }

        return (int) $viaje->user_id === $user->id
            ? Response::allow()
            : Response::deny('Sólo podés modificar los viajes que cargaste vos.');
    }

    /** Además de ser el creador, el viaje no debe tener ventas ni pagos en curso. */
    public function delete(User $user, Viaje $viaje): Response
    {
        $respuesta = $this->update($user, $viaje);

        if ($respuesta->denied()) {
            return $respuesta;
        }

        if ($viaje->pasajes()->exists()) {
            return Response::deny('No se puede eliminar: el viaje tiene pasajes vendidos.');
        }

        if ($viaje->reservasActivas()->exists()) {
            return Response::deny('No se puede eliminar: hay butacas reservadas con un pago en curso.');
        }

        return Response::allow();
    }
}
