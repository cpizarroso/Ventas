<?php

namespace App\Policies;

use App\Models\Promocion;
use App\Models\User;

class PromocionPolicy
{
    /**
     * Determine whether the user can view any promotions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create promotions.
     */
    public function create(User $user): bool
    {
        return $user->puedeGestionarConfiguracion() && $user->negocio_id !== null;
    }

    /**
     * Determine whether the user can update the promotion.
     */
    public function update(User $user, Promocion $promocion): bool
    {
        return $this->perteneceAlNegocioDelUsuario($user, $promocion);
    }

    /**
     * Determine whether the user can delete the promotion.
     */
    public function delete(User $user, Promocion $promocion): bool
    {
        return $this->perteneceAlNegocioDelUsuario($user, $promocion);
    }

    private function perteneceAlNegocioDelUsuario(User $user, Promocion $promocion): bool
    {
        return $user->puedeGestionarConfiguracion()
            && $user->negocio_id !== null
            && $promocion->negocio_id === $user->negocio_id;
    }
}
