<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Resuelve rutas de imágenes servidas directamente desde public/.
 *
 * Los productos creados por el seeder inicial apuntan a archivos que no viven
 * en el disk "public", sino en public/imagenes_iniciales y en el catálogo de
 * imágenes predeterminadas. El resto sigue usando Storage como siempre.
 */
final class ImagenPublica
{
    /** @var array<int, string> */
    private const PREFIJOS = [
        'imagenes_iniciales/',
        'img/productos-predeterminados/',
    ];

    public static function esPublica(string $ruta): bool
    {
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');

        foreach (self::PREFIJOS as $prefijo) {
            if (str_starts_with($ruta, $prefijo)) {
                return true;
            }
        }

        return false;
    }

    public static function url(string $ruta): string
    {
        if (! self::esPublica($ruta)) {
            return Storage::disk('public')->url($ruta);
        }

        $normalizada = ltrim(str_replace('\\', '/', $ruta), '/');

        return str_starts_with($normalizada, ImagenInicial::CARPETA.'/')
            ? ImagenInicial::url($normalizada)
            : asset($ruta);
    }
}
