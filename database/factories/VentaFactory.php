<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venta>
 */
class VentaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'negocio_id' => Negocio::factory(),
            'user_id' => null,
            'total' => fake()->randomFloat(2, 1, 500),
            'glosa' => null,
        ];
    }
}
