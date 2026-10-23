<?php

namespace App\Policies;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

class ProductoPolicy
{
    /**
     * Determine whether the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create products in the given category.
     */
    public function create(User $user, Categoria $categoria): bool
    {
        return $this->puedeGestionarCategoria($user, $categoria);
    }

    /**
     * Determine whether the user can update the product.
     */
    public function update(User $user, Producto $producto): bool
    {
        return $this->puedeGestionarCategoria($user, $producto->categoria);
    }

    /**
     * Determine whether the user can delete the product.
     */
    public function delete(User $user, Producto $producto): bool
    {
        return $this->puedeGestionarCategoria($user, $producto->categoria);
    }

    private function puedeGestionarCategoria(User $user, Categoria $categoria): bool
    {
        return $user->puedeGestionarConfiguracion()
            && $user->negocio_id !== null
            && $categoria->negocio_id === $user->negocio_id;
    }
}
