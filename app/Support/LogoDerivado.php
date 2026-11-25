<?php

namespace App\Support;

use App\Models\Negocio;
use Illuminate\Support\Facades\Storage;

/**
 * Small WebP version of the business logo for the interface.
 *
 * Uploaded logos are stored at up to 512px and can weigh hundreds of KB. The
 * full-size original is kept for reference, while the navigation, login and
 * receipt use this 256px derivative with its own cache-busting token.
 */
final class LogoDerivado
{
    public const CARPETA = 'logos/derivados';

    public const LADO = 256;

    public const CALIDAD = 85;

    public static function ruta(string $logo): string
    {
        return self::CARPETA.'/'.pathinfo($logo, PATHINFO_FILENAME).'.webp';
    }

    /**
     * Return the optimized URL, or the original one when no derivative exists.
     */
    public static function url(Negocio $negocio): ?string
    {
        $logo = $negocio->logo;

        if ($logo === null || $logo === '') {
            return null;
        }

        $disk = Storage::disk('public');
        $derivado = self::ruta($logo);

        if (! $disk->exists($derivado)) {
            return $negocio->logoUrl();
        }

        return $disk->url($derivado).'?v='.$disk->lastModified($derivado);
    }

    /**
     * Build (or refresh) the derivative from the stored original.
     */
    public static function sincronizar(Negocio $negocio): void
    {
        $logo = $negocio->logo;

        if ($logo === null || $logo === '') {
            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($logo) || ! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return;
        }

        $contenido = $disk->get($logo);
        $original = $contenido === null ? false : @imagecreatefromstring($contenido);

        if ($original === false) {
            return;
        }

        $ancho = imagesx($original);
        $alto = imagesy($original);
        $escala = min(1, self::LADO / max($ancho, $alto));
        $derivado = imagescale(
            $original,
            max(1, (int) round($ancho * $escala)),
            max(1, (int) round($alto * $escala)),
            IMG_BICUBIC,
        );
        imagedestroy($original);

        if ($derivado === false) {
            return;
        }

        imagealphablending($derivado, false);
        imagesavealpha($derivado, true);

        ob_start();
        $guardado = imagewebp($derivado, null, self::CALIDAD);
        $bytes = ob_get_clean();
        imagedestroy($derivado);

        if (! $guardado || ! is_string($bytes) || $bytes === '' || strlen($bytes) >= strlen((string) $contenido)) {
            return;
        }

        $disk->put(self::ruta($logo), $bytes, 'public');
    }

    public static function eliminar(?string $logo): void
    {
        if ($logo === null || $logo === '') {
            return;
        }

        Storage::disk('public')->delete(self::ruta($logo));
    }
}
