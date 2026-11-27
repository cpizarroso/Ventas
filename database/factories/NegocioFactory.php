<?php

namespace Database\Factories;

use App\Models\Negocio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Negocio>
 */
class NegocioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_comercial' => fake()->unique()->company(),
            'eslogan' => fake()->optional()->sentence(4),
            'logo' => null,
            'qr_pago' => null,
            'metodo_pago_predeterminado' => 'efectivo',
            'imprimir_ticket_predeterminado' => false,
            'mostrar_modal_impresion' => true,
            'stock_min' => 10,
            'permitir_stock_negativo' => false,
        ];
    }
}
