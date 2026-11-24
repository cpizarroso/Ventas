<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FaviconGenerator
{
    public const ARCHIVO = 'favicons/favicon.png';

    public const CONFIGURACION = 'favicons/favicon.json';

    public const TAMANO = 256;

    public const FUENTE_PREDETERMINADA = 'instrument';

    /** @var array<string, array{etiqueta: string, archivo: string}> */
    public const FUENTES = [
        'instrument' => ['etiqueta' => 'Instrument Sans', 'archivo' => 'InstrumentSans.ttf'],
        'inter' => ['etiqueta' => 'Inter', 'archivo' => 'Inter.ttf'],
        'space' => ['etiqueta' => 'Space Grotesk', 'archivo' => 'SpaceGrotesk.ttf'],
    ];

    /** @var array{int, int, int} amber-500 */
    private const COLOR_FONDO = [245, 158, 11];

    /** @var array{int, int, int} stone-900 */
    private const COLOR_TEXTO = [28, 25, 23];

    /**
     * @return array<string, string>
     */
    public static function etiquetasFuentes(): array
    {
        return collect(self::FUENTES)->map(fn (array $fuente): string => $fuente['etiqueta'])->all();
    }

    /**
     * Build a 256x256 PNG with the given 1-2 characters.
     */
    public function generar(string $texto, string $fuente = self::FUENTE_PREDETERMINADA): string
    {
        $texto = $this->normalizarTexto($texto);
        $fuente = $this->normalizarFuente($fuente);
        $rutaFuente = resource_path('fonts/'.self::FUENTES[$fuente]['archivo']);

        if (! is_file($rutaFuente)) {
            throw new RuntimeException('No se encontró la fuente del favicon.');
        }

        $imagen = imagecreatetruecolor(self::TAMANO, self::TAMANO);

        if ($imagen === false) {
            throw new RuntimeException('No se pudo crear la imagen del favicon.');
        }

        imagealphablending($imagen, false);
        imagesavealpha($imagen, true);
        imagefill($imagen, 0, 0, imagecolorallocatealpha($imagen, 0, 0, 0, 127));
        imagealphablending($imagen, true);

        $fondo = imagecolorallocate($imagen, ...self::COLOR_FONDO);
        $radio = 56;

        imagefilledrectangle($imagen, $radio, 0, self::TAMANO - $radio, self::TAMANO - 1, $fondo);
        imagefilledrectangle($imagen, 0, $radio, self::TAMANO - 1, self::TAMANO - $radio, $fondo);

        foreach ([[$radio, $radio], [self::TAMANO - $radio, $radio], [$radio, self::TAMANO - $radio], [self::TAMANO - $radio, self::TAMANO - $radio]] as [$cx, $cy]) {
            imagefilledellipse($imagen, $cx, $cy, $radio * 2, $radio * 2, $fondo);
        }

        $color = imagecolorallocate($imagen, ...self::COLOR_TEXTO);
        $tamano = 160;

        do {
            $caja = imagettfbbox($tamano, 0, $rutaFuente, $texto);
            $ancho = max($caja[2], $caja[4]) - min($caja[0], $caja[6]);
            $alto = max($caja[1], $caja[3]) - min($caja[5], $caja[7]);

            if ($ancho <= 178 && $alto <= 178) {
                break;
            }

            $tamano -= 4;
        } while ($tamano > 40);

        $x = (self::TAMANO - $ancho) / 2 - $caja[0];
        $y = (self::TAMANO - $alto) / 2 - $caja[7];

        imagettftext($imagen, $tamano, 0, (int) round($x), (int) round($y), $color, $rutaFuente, $texto);

        ob_start();
        $png = imagepng($imagen, null, 9);
        $contenido = ob_get_clean();
        imagedestroy($imagen);

        if ($png === false || $contenido === false) {
            throw new RuntimeException('No se pudo generar el PNG del favicon.');
        }

        return $contenido;
    }

    /**
     * Persist the generated icon, its text and its font.
     */
    public function guardar(string $texto, string $fuente = self::FUENTE_PREDETERMINADA): void
    {
        $texto = $this->normalizarTexto($texto);
        $fuente = $this->normalizarFuente($fuente);
        $disk = Storage::disk('public');

        $disk->put(self::ARCHIVO, $this->generar($texto, $fuente), 'public');
        $disk->put(
            self::CONFIGURACION,
            json_encode(['texto' => $texto, 'fuente' => $fuente], JSON_UNESCAPED_UNICODE),
            'public',
        );
    }

    /**
     * @return array{texto: string, fuente: string}
     */
    public function configuracionGuardada(): array
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::CONFIGURACION)) {
            return ['texto' => 'V', 'fuente' => self::FUENTE_PREDETERMINADA];
        }

        $datos = json_decode($disk->get(self::CONFIGURACION) ?? '', true);
        $texto = is_array($datos) ? (string) ($datos['texto'] ?? '') : '';
        $fuente = is_array($datos) ? (string) ($datos['fuente'] ?? '') : '';

        return [
            'texto' => preg_match('/^[\pL\pN]{1,2}$/u', $texto) === 1
                ? mb_strtoupper($texto, 'UTF-8')
                : 'V',
            'fuente' => array_key_exists($fuente, self::FUENTES)
                ? $fuente
                : self::FUENTE_PREDETERMINADA,
        ];
    }

    public function textoGuardado(): string
    {
        return $this->configuracionGuardada()['texto'];
    }

    /**
     * Cache-busting token for the stored favicon, or null when not generated.
     */
    public function version(): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists(self::ARCHIVO)) {
            return null;
        }

        $configuracion = $this->configuracionGuardada();

        return self::ARCHIVO
            .'@'.$configuracion['texto']
            .'@'.$configuracion['fuente']
            .'@'.$disk->lastModified(self::ARCHIVO);
    }

    public function vistaPrevia(string $texto, string $fuente = self::FUENTE_PREDETERMINADA): ?string
    {
        try {
            return 'data:image/png;base64,'.base64_encode($this->generar($texto, $fuente));
        } catch (RuntimeException) {
            return null;
        }
    }

    public function normalizarTexto(string $texto): string
    {
        $texto = mb_strtoupper(trim($texto), 'UTF-8');

        if (preg_match('/^[\pL\pN]{1,2}$/u', $texto) !== 1) {
            throw new RuntimeException('El favicon debe tener entre 1 y 2 letras o números.');
        }

        return $texto;
    }

    public function normalizarFuente(string $fuente): string
    {
        if (! array_key_exists($fuente, self::FUENTES)) {
            throw new RuntimeException('La tipografía del favicon no es válida.');
        }

        return $fuente;
    }
}
