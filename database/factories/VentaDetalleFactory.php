<?php

namespace Database\Factories;

use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VentaDetalle>
 */
class VentaDetalleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venta_id' => Venta::factory(),
            'producto_id' => null,
            'nombre' => fake()->words(2, true),
            'precio_unitario' => fake()->randomFloat(2, 1, 100),
            'cantidad' => fake()->numberBetween(1, 5),
            'subtotal' => fn (array $attributes) => round($attributes['precio_unitario'] * $attributes['cantidad'], 2),
        ];
    }
}
