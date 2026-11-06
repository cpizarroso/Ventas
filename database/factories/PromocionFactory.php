<?php

namespace Database\Factories;

use App\Models\Negocio;
use App\Models\Promocion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promocion>
 */
class PromocionFactory extends Factory
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
            'nombre' => fake()->unique()->words(3, true),
            'descripcion' => null,
            'precio' => fake()->randomFloat(2, 5, 100),
            'fecha_inicio' => now()->startOfDay()->toDateString(),
            'fecha_fin' => now()->addWeek()->toDateString(),
            'dias_semana' => [1, 2, 3, 4, 5, 6, 7],
            'activa' => true,
        ];
    }

    public function inactiva(): static
    {
        return $this->state(fn (array $attributes) => [
            'activa' => false,
        ]);
    }

    public function vencida(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_inicio' => now()->subWeek()->toDateString(),
            'fecha_fin' => now()->subDay()->toDateString(),
        ]);
    }

    public function programada(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_inicio' => now()->addDay()->toDateString(),
            'fecha_fin' => now()->addWeek()->toDateString(),
        ]);
    }
}
