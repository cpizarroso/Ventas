<?php

namespace App\Support;

use App\Models\Producto;
use App\Models\Promocion;

/**
 * Single source of truth for promotion availability, shared by the POS catalog
 * and the checkout validation.
 */
final class DisponibilidadPromocion
{
    public static function estaDisponible(Promocion $promocion): bool
    {
        if (! $promocion->activa) {
            return false;
        }

        $hoy = now()->startOfDay();

        if ($promocion->fecha_inicio->gt($hoy) || $promocion->fecha_fin->lt($hoy)) {
            return false;
        }

        if (! in_array($hoy->isoWeekday(), array_map('intval', $promocion->dias_semana ?? []), true)) {
            return false;
        }

        if ($promocion->productos->isEmpty()) {
            return false;
        }

        return $promocion->productos->every(
            fn (Producto $producto): bool => $producto->activa
                && $producto->categoria?->activa !== false
        );
    }
}
