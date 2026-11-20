<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\AceptacionTerminos;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    private const USUARIOS = [
        'admin@jibas.test',
        'cajero1@jibas.test',
        'cajero2@jibas.test',
        'jpbalcazar@jibas.test',
    ];

    private const OBSOLETOS = [
        'superadmin@jibas.test',
        'cajero@jibas.test',
        'cajero-reporte@jibas.test',
    ];

    public function test_el_seeder_de_usuarios_crea_los_cuatro_y_es_idempotente(): void
    {
        Negocio::factory()->create();

        $this->seed(SuperadminSeeder::class);
        $this->seed(SuperadminSeeder::class);

        $this->assertSame(self::USUARIOS, User::query()->orderBy('id')->pluck('email')->all());
        $this->assertSame(0, User::query()->whereIn('email', self::OBSOLETOS)->count());
        $this->assertSame(Rol::Admin, User::query()->where('email', 'jpbalcazar@jibas.test')->value('rol'));
        $this->assertSame(2, User::query()->where('rol', Rol::Cajero->value)->count());
        $this->assertSame(4, AceptacionTerminos::count());
        $this->assertSame(0, Categoria::count());
    }

    public function test_el_seeder_principal_prepara_el_escenario_completo_y_es_idempotente(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Negocio::query()->where('nombre_comercial', 'Mi Negocio')->count());
        $this->assertSame(self::USUARIOS, User::query()->orderBy('id')->pluck('email')->all());
        $this->assertSame(0, User::query()->whereIn('email', self::OBSOLETOS)->count());
        $this->assertSame(4, AceptacionTerminos::count());
        $this->assertSame(3, Categoria::count());
        $this->assertSame(14, Producto::count());
        $this->assertSame(500, Venta::count());
        $this->assertSame(now()->endOfMonth()->day, Caja::count());
    }

    public function test_al_crear_un_negocio_por_fabrica_no_se_generan_categorias_ficticias(): void
    {
        $negocio = Negocio::factory()->create();

        $this->assertSame(0, $negocio->categorias()->count());
    }
}
