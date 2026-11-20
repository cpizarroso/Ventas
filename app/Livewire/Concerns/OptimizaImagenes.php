<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait OptimizaImagenes
{
    private const PERFIL_LOGO = 'logo';

    private const PERFIL_PRODUCTO = 'producto';

    private const PERFIL_QR = 'qr';

    private const TAMANO_LOGO = 512;

    private const TAMANO_PRODUCTO = 800;

    private const CALIDAD_JPG_LOGO = 88;

    private const CALIDAD_JPG_PRODUCTO = 85;

    private const CALIDAD_WEBP_LOGO = 85;

    private const CALIDAD_WEBP_PRODUCTO = 82;

    private const NIVEL_PNG = 9;

    protected function guardarImagenOptimizada(
        TemporaryUploadedFile|string $origen,
        string $carpeta,
        string $perfil,
        ?string $mime = null,
    ): string {
        $rutaOrigen = $origen instanceof TemporaryUploadedFile ? $origen->getRealPath() : $origen;
        $mime ??= $origen instanceof TemporaryUploadedFile
            ? $origen->getMimeType()
            : (File::mimeType($rutaOrigen) ?: 'image/jpeg');

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $ruta = $carpeta.'/'.Str::random(40).'.'.$extension;
        $contenido = @file_get_contents($rutaOrigen);

        Storage::disk('public')->put(
            $ruta,
            $contenido === false ? '' : ($this->optimizarBytes($contenido, $extension, $perfil) ?? $contenido),
        );

        return $ruta;
    }

    private function optimizarBytes(string $contenido, string $extension, string $perfil): ?string
    {
        if ($perfil === self::PERFIL_QR && $extension !== 'png') {
            return null;
        }

        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $origen = @imagecreatefromstring($contenido);

        if ($origen === false) {
            return null;
        }

        $destino = $this->transformarImagen($origen, $perfil);
        $bytes = $this->codificarImagen($destino, $extension, $perfil);

        imagedestroy($origen);

        if ($destino !== $origen) {
            imagedestroy($destino);
        }

        if ($bytes === false || $bytes === '') {
            return null;
        }

        if ($perfil !== self::PERFIL_PRODUCTO && strlen($bytes) >= strlen($contenido)) {
            return null;
        }

        return $bytes;
    }

    private function transformarImagen(\GdImage $origen, string $perfil): \GdImage
    {
        $ancho = imagesx($origen);
        $alto = imagesy($origen);

        if ($perfil === self::PERFIL_QR) {
            return $origen;
        }

        if ($perfil === self::PERFIL_PRODUCTO) {
            $lado = min($ancho, $alto);
            $tamano = min(self::TAMANO_PRODUCTO, $lado);

            return $this->copiarImagen(
                $origen,
                $tamano,
                $tamano,
                (int) floor(($ancho - $lado) / 2),
                (int) floor(($alto - $lado) / 2),
                $lado,
                $lado,
            );
        }

        $escala = min(1, self::TAMANO_LOGO / max($ancho, $alto));

        return $this->copiarImagen(
            $origen,
            max(1, (int) round($ancho * $escala)),
            max(1, (int) round($alto * $escala)),
            0,
            0,
            $ancho,
            $alto,
        );
    }

    private function copiarImagen(
        \GdImage $origen,
        int $anchoDestino,
        int $altoDestino,
        int $xOrigen,
        int $yOrigen,
        int $anchoOrigen,
        int $altoOrigen,
    ): \GdImage {
        $destino = imagecreatetruecolor($anchoDestino, $altoDestino);

        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagefill($destino, 0, 0, imagecolorallocatealpha($destino, 0, 0, 0, 127));

        imagecopyresampled(
            $destino,
            $origen,
            0,
            0,
            $xOrigen,
            $yOrigen,
            $anchoDestino,
            $altoDestino,
            $anchoOrigen,
            $altoOrigen,
        );

        return $destino;
    }

    private function codificarImagen(\GdImage $imagen, string $extension, string $perfil): string|false
    {
        ob_start();

        $guardado = match ($extension) {
            'png' => imagepng($imagen, null, self::NIVEL_PNG),
            'webp' => imagewebp(
                $imagen,
                null,
                $perfil === self::PERFIL_LOGO ? self::CALIDAD_WEBP_LOGO : self::CALIDAD_WEBP_PRODUCTO,
            ),
            default => imagejpeg(
                $imagen,
                null,
                $perfil === self::PERFIL_LOGO ? self::CALIDAD_JPG_LOGO : self::CALIDAD_JPG_PRODUCTO,
            ),
        };

        $bytes = ob_get_clean();

        return $guardado ? $bytes : false;
    }
}
