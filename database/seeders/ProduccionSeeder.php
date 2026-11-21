<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class ProduccionSeeder extends Seeder
{
    /**
     * Prepara un escenario limpio de producción sin usuarios ni datos de prueba.
     *
     * Los usuarios los crea SuperadminSeeder, llamado desde DatabaseSeeder.
     */
    public function run(): void
    {
        $this->limpiarDatosDePrueba();

        $negocio = Negocio::firstOrCreate(
            ['nombre_comercial' => DatabaseSeeder::NOMBRE_COMERCIAL],
            ['eslogan' => DatabaseSeeder::ESLOGAN, 'logo' => null]
        );

        $this->crearCatalogoInicial($negocio);
    }

    private function limpiarDatosDePrueba(): void
    {
        Venta::query()->delete();
        Caja::query()->delete();
        Categoria::query()->delete();
    }

    private function crearCatalogoInicial(Negocio $negocio): void
    {
        $catalogo = [
            [
                'nombre' => 'PACUMUTOS',
                'orden' => 1,
                'productos' => [
                    'Jiba' => 12.00,
                    'Morcilla' => 9.00,
                    'Chorizo' => 10.00,
                    'Arepa' => 8.00,
                    'Pacumuto especial' => 14.00,
                ],
            ],
            [
                'nombre' => 'REFRESCOS 2Lt',
                'orden' => 2,
                'productos' => [
                    'Refresco 2Lt' => 15.00,
                    'Gaseosa 2Lt' => 14.00,
                    'Jugo 2Lt' => 16.00,
                    'Soda 2Lt' => 13.00,
                ],
            ],
            [
                'nombre' => 'REFRESCOS 500ml',
                'orden' => 3,
                'productos' => [
                    'Agua 500 ml' => 7.00,
                    'Gaseosa 500 ml' => 8.00,
                    'Jugo 500 ml' => 9.00,
                    'Soda 500 ml' => 7.50,
                    'Refresco 500 ml' => 8.50,
                ],
            ],
        ];

        foreach ($catalogo as $categoriaConfig) {
            $categoria = Categoria::firstOrCreate(
                [
                    'negocio_id' => $negocio->id,
                    'nombre' => $categoriaConfig['nombre'],
                ],
                [
                    'activa' => true,
                    'orden' => $categoriaConfig['orden'],
                ]
            );

            foreach ($categoriaConfig['productos'] as $nombre => $precio) {
                Producto::firstOrCreate(
                    [
                        'categoria_id' => $categoria->id,
                        'nombre' => $nombre,
                    ],
                    [
                        'precio' => $precio,
                        'stock' => null,
                    ]
                );
            }
        }
    }
}
