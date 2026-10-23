<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\User;

class CategoriaPolicy
{
    /**
     * Determine whether the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->puedeGestionarConfiguracion() && $user->negocio_id !== null;
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, Categoria $categoria): bool
    {
        return $this->perteneceAlNegocioDelUsuario($user, $categoria);
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, Categoria $categoria): bool
    {
        return $this->perteneceAlNegocioDelUsuario($user, $categoria);
    }

    private function perteneceAlNegocioDelUsuario(User $user, Categoria $categoria): bool
    {
        return $user->puedeGestionarConfiguracion()
            && $user->negocio_id !== null
            && $categoria->negocio_id === $user->negocio_id;
    }
}
