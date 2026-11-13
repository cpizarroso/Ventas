<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;

class CajaPolicy
{
    /**
     * Determine whether the user can view cash registers.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the cash register.
     */
    public function view(User $user, Caja $caja): bool
    {
        return $this->perteneceAlNegocioDelUsuario($user, $caja)
            && ($caja->user_id === $user->id || $user->esAdmin() || $user->esSuperadmin());
    }

    /**
     * Determine whether the user can open a cash register.
     */
    public function create(User $user): bool
    {
        return $user->negocio_id !== null;
    }

    /**
     * Determine whether the user can close the cash register.
     */
    public function cerrar(User $user, Caja $caja): bool
    {
        return $this->view($user, $caja) && $caja->estaAbierta();
    }

    private function perteneceAlNegocioDelUsuario(User $user, Caja $caja): bool
    {
        return $user->negocio_id !== null
            && $caja->negocio_id === $user->negocio_id;
    }
}
