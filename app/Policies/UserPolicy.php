<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can manage users of a business.
     */
    public function viewAny(User $user): bool
    {
        return $user->puedeGestionarConfiguracion() && $user->negocio_id !== null;
    }

    /**
     * Determine whether the user can create users in their business.
     */
    public function create(User $user): bool
    {
        return $user->puedeGestionarConfiguracion() && $user->negocio_id !== null;
    }

    /**
     * Determine whether the user can update a user of their business.
     */
    public function update(User $user, User $model): bool
    {
        return $this->perteneceAlNegocioDelUsuario($user, $model);
    }

    /**
     * Determine whether the user can delete a user of their business.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id
            && $this->perteneceAlNegocioDelUsuario($user, $model);
    }

    private function perteneceAlNegocioDelUsuario(User $user, User $model): bool
    {
        return $user->puedeGestionarConfiguracion()
            && $user->negocio_id !== null
            && $model->negocio_id === $user->negocio_id;
    }
}
