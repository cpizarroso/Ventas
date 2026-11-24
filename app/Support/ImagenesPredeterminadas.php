<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Catálogo de imágenes predeterminadas y sus derivados por tamaño.
 *
 * El manifiesto guarda qué tamaños existen realmente para cada imagen, de modo
 * que las imágenes antiguas de 256px se sigan sirviendo a 256px sin estirarlas.
 */
final class ImagenesPredeterminadas
{
    private const MANIFEST = 'manifest.json';

    private const CARPETA_MAESTRAS = 'maestras';

    /**
     * @return array<string, array{archivo: string, sizes: array<int>, legacy: bool}>
     */
    public static function todos(): array
    {
        $ruta = self::directorio().DIRECTORY_SEPARATOR.self::MANIFEST;

        if (! File::exists($ruta)) {
            return [];
        }

        $datos = json_decode((string) File::get($ruta), true);

        return is_array($datos) ? $datos : [];
    }

    public static function directorio(): string
    {
        return (string) config('productos.imagenes_predeterminadas', public_path('img/productos-predeterminados'));
    }

    public static function urlBase(): string
    {
        return '/'.trim((string) config('productos.url_imagenes_predeterminadas', '/img/productos-predeterminadas'), '/');
    }

    public static function url(string $id, int $tamano): string
    {
        $entrada = self::todos()[$id] ?? null;

        if ($entrada === null) {
            return '';
        }

        $real = self::mejorTamano($entrada, $tamano);

        return asset(self::urlBase().'/'.$real.'/'.$entrada['archivo']);
    }

    /**
     * Devuelve el tamaño disponible más cercano sin superar el solicitado.
     *
     * @param  array{sizes: array<int>}  $entrada
     */
    public static function mejorTamano(array $entrada, int $solicitado): int
    {
        $sizes = array_values(array_map('intval', $entrada['sizes'] ?? []));

        if ($sizes === []) {
            return $solicitado;
        }

        $candidatos = array_values(array_filter($sizes, fn (int $s): bool => $s <= $solicitado));

        return $candidatos === [] ? min($sizes) : max($candidatos);
    }

    public static function rutaDerivado(string $id, int $tamano): string
    {
        $entrada = self::todos()[$id] ?? null;

        if ($entrada === null) {
            return '';
        }

        $real = self::mejorTamano($entrada, $tamano);

        return self::directorio().DIRECTORY_SEPARATOR.$real.DIRECTORY_SEPARATOR.$entrada['archivo'];
    }

    public static function rutaMaestra(string $id): string
    {
        $entrada = self::todos()[$id] ?? null;

        if ($entrada === null || ($entrada['legacy'] ?? false)) {
            return '';
        }

        return self::directorio().DIRECTORY_SEPARATOR.self::CARPETA_MAESTRAS.DIRECTORY_SEPARATOR.$entrada['archivo'];
    }

    public static function rutaMaestraPara(string $archivo): string
    {
        return self::directorio().DIRECTORY_SEPARATOR.self::CARPETA_MAESTRAS.DIRECTORY_SEPARATOR.$archivo;
    }

    public static function rutaDerivadoPara(int $tamano, string $archivo): string
    {
        return self::directorio().DIRECTORY_SEPARATOR.$tamano.DIRECTORY_SEPARATOR.$archivo;
    }

    public static function siguienteId(): string
    {
        $numeros = [];

        foreach (array_keys(self::todos()) as $id) {
            if (preg_match('/^producto_(\d+)\./', (string) $id, $coincidencia) === 1) {
                $numeros[] = (int) $coincidencia[1];
            }
        }

        $siguiente = $numeros === [] ? 1 : max($numeros) + 1;

        return 'producto_'.str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT).'.webp';
    }

    /**
     * @param  array<string, array{archivo: string, sizes: array<int>, legacy: bool}>  $todos
     */
    public static function escribir(array $todos): void
    {
        ksort($todos);

        File::ensureDirectoryExists(self::directorio());
        File::put(
            self::directorio().DIRECTORY_SEPARATOR.self::MANIFEST,
            json_encode($todos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }
}
