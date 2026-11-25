<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Catálogo inicial de un restaurante clásico de pollos.
 *
 * Se ejecuta de forma explícita para no sobrescribir el catálogo que ya tenga
 * cada negocio:
 *
 *     php artisan db:seed --class=RestaurantePollosSeeder
 */
class RestaurantePollosSeeder extends Seeder
{
    private const CARPETA_IMAGENES = 'imagenes_iniciales/';

    /** @var array<string, array<string, string>> */
    private array $nombresReales = [];

    public function run(): void
    {
        $negocio = Negocio::query()->oldest('id')->first();

        if ($negocio === null) {
            throw new \RuntimeException('No existe un negocio. Ejecuta primero php artisan db:seed.');
        }

        foreach ($this->catalogo() as $config) {
            $categoria = Categoria::updateOrCreate(
                [
                    'negocio_id' => $negocio->id,
                    'nombre' => $config['nombre'],
                ],
                [
                    'activa' => true,
                    'orden' => $config['orden'],
                ]
            );

            foreach ($config['productos'] as $nombre => $producto) {
                $valores = [
                    'precio' => $producto['precio'],
                    'stock' => null,
                ];

                $imagen = $this->imagenPara(
                    $config,
                    $nombre,
                    $producto['imagen'],
                    $producto['imagen_marca'] ?? null
                );

                // Si no hay ninguna imagen disponible se conserva la que ya
                // tuviera el producto, en vez de borrársela al repetir el seed.
                if ($imagen !== null) {
                    $valores['imagen'] = $imagen;
                }

                Producto::updateOrCreate(
                    [
                        'categoria_id' => $categoria->id,
                        'nombre' => $nombre,
                    ],
                    $valores
                );
            }
        }
    }

    /**
     * @return array<int, array{nombre: string, orden: int, imagen_categoria: string, imagen_generica: string, productos: array<string, array{precio: float, imagen: string, imagen_marca?: string}>}>
     */
    private function catalogo(): array
    {
        $pollos = [];

        foreach ([
            'Económico' => ['precio' => 15.00, 'imagen' => 'Pollo-Económico.png'],
            'Cuarto' => ['precio' => 25.00, 'imagen' => 'Pollo-Cuarto.png'],
            'Medio' => ['precio' => 50.00, 'imagen' => 'Pollo-Medio.png'],
            'Entero' => ['precio' => 100.00, 'imagen' => 'Pollo-Entero.png'],
        ] as $corte => $config) {
            $pollos["Pollo a la Brasa {$corte}"] = $config;
            $pollos["Broasted {$corte}"] = $config;
        }

        $gaseosas = [];

        foreach (['Coca Cola' => 'Coca-Cola', 'Fanta' => 'Fanta', 'Sprite' => 'Sprite'] as $marca => $slug) {
            foreach ([
                '3Lt' => 18.00,
                '2Lt' => 15.00,
                '1Lt' => 10.00,
                'Popular' => 8.00,
                'Personal' => 5.00,
                'mini' => 3.00,
            ] as $presentacion => $precio) {
                $gaseosas["{$marca} {$presentacion}"] = [
                    'precio' => $precio,
                    'imagen' => "Gaseosa-{$slug}-{$presentacion}.png",
                    'imagen_marca' => Str::lower($slug).'.png',
                ];
            }
        }

        return [
            [
                'nombre' => 'Pollo',
                'orden' => 1,
                'imagen_categoria' => 'pollo.png',
                'imagen_generica' => 'img/productos-predeterminados/256/producto_001.png',
                'productos' => $pollos,
            ],
            [
                'nombre' => 'Gaseosa',
                'orden' => 2,
                'imagen_categoria' => 'gaseosa.png',
                'imagen_generica' => 'img/productos-predeterminados/256/producto_002.png',
                'productos' => $gaseosas,
            ],
        ];
    }

    /**
     * @param  array{nombre: string, imagen_categoria: string, imagen_generica: string}  $categoria
     */
    private function imagenPara(
        array $categoria,
        string $nombreProducto,
        string $archivo,
        ?string $archivoMarca = null
    ): ?string {
        $candidatos = [
            self::CARPETA_IMAGENES.$archivo,
            self::CARPETA_IMAGENES.Str::ascii(Str::lower($archivo)),
        ];

        if ($archivoMarca !== null) {
            $candidatos[] = self::CARPETA_IMAGENES.$archivoMarca;
            $candidatos[] = self::CARPETA_IMAGENES.Str::ascii(Str::lower($archivoMarca));
        }

        $candidatos[] = self::CARPETA_IMAGENES.$categoria['imagen_categoria'];
        $candidatos[] = $categoria['imagen_generica'];

        foreach (array_unique($candidatos) as $ruta) {
            $real = $this->rutaReal($ruta);

            if ($real !== null) {
                return $real;
            }
        }

        $this->command?->warn("Sin imagen para {$nombreProducto}; se deja sin imagen.");

        return null;
    }

    /**
     * Devuelve la ruta con la capitalización exacta del archivo en disco.
     *
     * En Windows una comparación case-insensitive encontraría "Pollo-Cuarto.png"
     * aunque el archivo se llame "pollo-cuarto.png"; en Linux esa ruta daría 404.
     */
    private function rutaReal(string $ruta): ?string
    {
        $directorio = str_replace('\\', '/', dirname($ruta));
        $archivo = basename($ruta);
        $nombreReal = $this->nombresReales($directorio)[Str::lower($archivo)] ?? null;

        if ($nombreReal === null) {
            return null;
        }

        return $directorio === '.' ? $nombreReal : $directorio.'/'.$nombreReal;
    }

    /**
     * @return array<string, string>
     */
    private function nombresReales(string $directorio): array
    {
        if (! isset($this->nombresReales[$directorio])) {
            $mapa = [];
            $absoluto = public_path($directorio);

            if (File::isDirectory($absoluto)) {
                foreach (File::files($absoluto) as $archivo) {
                    $mapa[Str::lower($archivo->getFilename())] = $archivo->getFilename();
                }
            }

            $this->nombresReales[$directorio] = $mapa;
        }

        return $this->nombresReales[$directorio];
    }
}
