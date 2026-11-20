<?php

namespace Database\Seeders;

use App\Models\Negocio;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Prepara el escenario base de forma idempotente.
     *
     * Crea el negocio principal y delega usuarios, catálogo y datos de reporte
     * en sus respectivos seeders.
     */
    public function run(): void
    {
        Negocio::firstOrCreate(
            ['nombre_comercial' => 'Mi Negocio'],
            ['eslogan' => null, 'logo' => null]
        );

        $this->call([
            SuperadminSeeder::class,
            ProduccionSeeder::class,
            ReportePruebasSeeder::class,
        ]);
    }
}
