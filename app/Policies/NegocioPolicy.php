<?php

namespace App\Policies;

use App\Models\Negocio;
use App\Models\User;

class NegocioPolicy
{
    /**
     * Determine whether the user can update the business identity.
     */
    public function update(User $user, Negocio $negocio): bool
    {
        return $user->puedeGestionarConfiguracion()
            && $user->negocio_id !== null
            && $negocio->id === $user->negocio_id;
    }
}
