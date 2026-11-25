<?php

namespace Database\Seeders;

use App\Models\Negocio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public const NOMBRE_COMERCIAL = 'JOTAPE';

    public const ESLOGAN = 'Jibas frescas, sabor de siempre';

    /**
     * Prepara el escenario base de forma idempotente.
     *
     * Vacía todas las tablas (excepto migrations) y reinicia su numeración
     * antes de crear el negocio principal, los usuarios base y el catálogo
     * inicial del restaurante de pollos.
     */
    public function run(): void
    {
        $this->reiniciarBaseDeDatos();

        Negocio::firstOrCreate(
            ['nombre_comercial' => self::NOMBRE_COMERCIAL],
            ['eslogan' => self::ESLOGAN, 'logo' => null, 'metodo_pago_predeterminado' => 'efectivo']
        );

        $this->call([
            SuperadminSeeder::class,
            RestaurantePollosSeeder::class,
        ]);
    }

    /**
     * Borra las filas en orden seguro para las claves foráneas y reinicia los IDs.
     */
    private function reiniciarBaseDeDatos(): void
    {
        foreach ($this->tablasParaReiniciar() as $tabla) {
            DB::table($tabla)->delete();
            $this->reiniciarNumeracion($tabla);
        }
    }

    /**
     * @return array<int, string>
     */
    private function tablasParaReiniciar(): array
    {
        return [
            'aceptaciones_terminos',
            'venta_detalle_promocion_items',
            'venta_detalles',
            'ventas',
            'cajas',
            'promocion_productos',
            'promociones',
            'productos',
            'categorias',
            'users',
            'negocios',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ];
    }

    private function reiniciarNumeracion(string $tabla): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE `'.$tabla.'` AUTO_INCREMENT = 1');

            return;
        }

        if ($driver === 'sqlite' && Schema::hasTable('sqlite_sequence')) {
            DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$tabla]);
        }
    }
}
