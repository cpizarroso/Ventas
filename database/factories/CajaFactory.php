<?php

namespace Database\Factories;

use App\Enums\EstadoCaja;
use App\Models\Caja;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Caja>
 */
class CajaFactory extends Factory
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
            'user_id' => User::factory(),
            'cerrada_por_id' => null,
            'estado' => EstadoCaja::Abierta,
            'monto_inicial' => fake()->randomFloat(2, 0, 200),
            'observaciones_apertura' => null,
            'fecha_apertura' => now(),
            'total_ventas' => 0,
            'total_efectivo' => 0,
            'total_qr' => 0,
            'cantidad_ventas' => 0,
            'monto_cierre' => null,
            'monto_esperado_efectivo' => null,
            'diferencia' => null,
            'observaciones_cierre' => null,
            'fecha_cierre' => null,
        ];
    }

    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoCaja::Abierta,
            'fecha_cierre' => null,
            'cerrada_por_id' => null,
        ]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => EstadoCaja::Cerrada,
            'fecha_cierre' => now(),
        ]);
    }
}
