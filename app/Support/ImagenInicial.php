<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Derivado ligero de las imágenes iniciales servidas desde public/.
 *
 * Las imágenes que traiga el restaurante pueden pesar varios cientos de KB.
 * El catálogo usa una versión WebP de 256px y mantiene el original como
 * respaldo si el derivado no existe o no se pudo generar.
 */
final class ImagenInicial
{
    public const CARPETA = 'imagenes_iniciales';

    private const DERIVADOS = 'derivados';

    private const LADO = 256;

    private const CALIDAD = 85;

    public static function rutaDerivada(string $ruta): string
    {
        $relativa = ltrim(str_replace('\\', '/', $ruta), '/');

        if (! str_starts_with($relativa, self::CARPETA.'/')) {
            return '';
        }

        return self::CARPETA.'/'.self::DERIVADOS.'/'.pathinfo($relativa, PATHINFO_FILENAME).'.webp';
    }

    public static function url(string $ruta): string
    {
        $derivada = self::rutaDerivada($ruta);

        if ($derivada !== '' && File::exists(public_path($derivada))) {
            return asset($derivada).'?v='.filemtime(public_path($derivada));
        }

        return asset($ruta);
    }

    /**
     * Genera o refresca el derivado cuando el original es más nuevo.
     */
    public static function sincronizar(string $ruta): bool
    {
        $derivada = self::rutaDerivada($ruta);

        if ($derivada === '') {
            return false;
        }

        $original = public_path($ruta);
        $destino = public_path($derivada);

        if (! File::exists($original)) {
            return false;
        }

        if (File::exists($destino) && filemtime($destino) >= filemtime($original)) {
            return false;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return false;
        }

        $contenido = File::get($original);
        $imagen = @imagecreatefromstring($contenido);

        if ($imagen === false) {
            return false;
        }

        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $escala = min(1, self::LADO / max($ancho, $alto));
        $reducida = imagescale(
            $imagen,
            max(1, (int) round($ancho * $escala)),
            max(1, (int) round($alto * $escala)),
            IMG_BICUBIC,
        );
        imagedestroy($imagen);

        if ($reducida === false) {
            return false;
        }

        imagealphablending($reducida, false);
        imagesavealpha($reducida, true);

        ob_start();
        $guardado = imagewebp($reducida, null, self::CALIDAD);
        $bytes = ob_get_clean();
        imagedestroy($reducida);

        if (! $guardado || ! is_string($bytes) || $bytes === '' || strlen($bytes) >= strlen($contenido)) {
            return false;
        }

        if (! File::isDirectory(dirname($destino))) {
            File::makeDirectory(dirname($destino), 0755, true);
        }

        return File::put($destino, $bytes) !== false;
    }
}
