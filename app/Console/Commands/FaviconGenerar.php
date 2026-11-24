<?php

namespace App\Console\Commands;

use App\Services\FaviconGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FaviconGenerar extends Command
{
    /** @var string */
    protected $signature = 'favicon:generar {texto=V : Letras del favicon (1 o 2)} {fuente=instrument : Tipografía (instrument, inter, space)}';

    /** @var string */
    protected $description = 'Genera el favicon global y el archivo .ico de respaldo';

    public function handle(FaviconGenerator $generador): int
    {
        $texto = $generador->normalizarTexto((string) $this->argument('texto'));
        $fuente = $generador->normalizarFuente((string) $this->argument('fuente'));

        $generador->guardar($texto, $fuente);
        $png = Storage::disk('public')->get(FaviconGenerator::ARCHIVO);

        $ico = pack('vvv', 0, 1, 1)
            .pack('CCCCvvVV', 0, 0, 0, 0, 1, 32, strlen($png), 22)
            .$png;

        file_put_contents(public_path('favicon.ico'), $ico);

        $this->info("Favicon generado con: {$texto}");

        return self::SUCCESS;
    }
}
