<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Support\ImagenInicial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Catálogo inicial de gaseosas: tres marcas en seis presentaciones.
 *
 * Se ejecuta desde DatabaseSeeder y también de forma explícita:
 *
 *     php artisan db:seed --class=GaseosaSeeder
 */
class GaseosaSeeder extends Seeder
{
    private const CARPETA_IMAGENES = 'imagenes_iniciales/';

    private const STOCK_INICIAL = 1000;

    private const ORDEN_CATEGORIA = 2;

    private const IMAGEN_GENERICA = 'img/productos-predeterminados/256/producto_002.png';

    /** @var array<string, array<string, string>> */
    private array $nombresReales = [];

    public function run(): void
    {
        $negocio = Negocio::query()->oldest('id')->first();

        if ($negocio === null) {
            throw new \RuntimeException('No existe un negocio. Ejecuta primero php artisan db:seed.');
        }

        $categoria = Categoria::updateOrCreate(
            [
                'negocio_id' => $negocio->id,
                'nombre' => 'Gaseosa',
            ],
            [
                'activa' => true,
                'orden' => self::ORDEN_CATEGORIA,
            ]
        );

        foreach ($this->catalogo() as $nombre => $producto) {
            $valores = [
                'nombre' => $nombre,
                'precio' => $producto['precio'],
                'stock' => self::STOCK_INICIAL,
            ];

            $imagen = $this->imagenPara($producto['imagen'], $producto['imagen_marca']);

            // Si no hay imagen disponible se conserva la que ya tuviera el
            // producto, en vez de borrársela al repetir el seed.
            if ($imagen !== null) {
                $valores['imagen'] = $imagen;
                ImagenInicial::sincronizar($imagen);
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

    /**
     * @return array<string, array{precio: float, imagen: string, imagen_marca: string}>
     */
    private function catalogo(): array
    {
        $productos = [];

        foreach ([
            'Coca Cola' => ['coca cola', 'coca-cola.jpg'],
            'Fanta' => ['fanta', 'fanta.jpg'],
            'Sprite' => ['sprite', 'sprite.jpg'],
        ] as $marca => [$slug, $imagenMarca]) {
            foreach ([
                '3lt' => 25.00,
                '2lt' => 20.00,
                '1lt' => 15.00,
                '500ml' => 10.00,
                'mini' => 3.00,
                'peque' => 4.00,
            ] as $presentacion => $precio) {
                $productos["{$marca} {$presentacion}"] = [
                    'precio' => $precio,
                    'imagen' => "{$slug} {$presentacion}.jpg",
                    'imagen_marca' => $imagenMarca,
                ];
            }
        }

        return $productos;
    }

    private function imagenPara(string $archivo, string $archivoMarca): ?string
    {
        $candidatos = [
            self::CARPETA_IMAGENES.$archivo,
            self::CARPETA_IMAGENES.Str::ascii(Str::lower($archivo)),
            self::CARPETA_IMAGENES.'gaseosa.png',
            self::CARPETA_IMAGENES.$archivoMarca,
            self::CARPETA_IMAGENES.Str::ascii(Str::lower($archivoMarca)),
            self::IMAGEN_GENERICA,
        ];

        foreach (array_unique($candidatos) as $ruta) {
            $real = $this->rutaReal($ruta);

            if ($real !== null) {
                return $real;
            }
        }

        $this->command?->warn("Sin imagen para {$archivo}; se deja sin imagen.");

        return null;
    }

    /**
     * Devuelve la ruta con la capitalización exacta del archivo en disco.
     *
     * En Windows una comparación case-insensitive encontraría "Fanta 1lt.jpg"
     * aunque el archivo se llame "fanta 1lt.jpg"; en Linux esa ruta daría 404.
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
