<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_seeders_no_creen_categorias_predeterminadas(): void
    {
        $this->seed();

        $this->assertSame(0, Categoria::count());
    }

    public function test_el_seeder_del_superadmin_es_idempotente_y_no_crea_categorias(): void
    {
        $this->seed(SuperadminSeeder::class);
        $this->seed(SuperadminSeeder::class);

        $this->assertSame(1, User::where('email', 'superadmin@jibas.test')->count());
        $this->assertSame('superadmin', User::where('email', 'superadmin@jibas.test')->value('username'));
        $this->assertSame(0, Categoria::count());
    }

    public function test_el_seeder_principal_es_idempotente(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Negocio::where('nombre_comercial', 'Mi Negocio')->count());
        $this->assertSame(1, User::where('email', 'admin@jibas.test')->count());
        $this->assertSame(1, User::where('email', 'cajero@jibas.test')->count());
        $this->assertSame(1, User::where('email', 'superadmin@jibas.test')->count());
        $this->assertSame(1, User::where('username', 'admin')->count());
        $this->assertSame(1, User::where('username', 'cajero')->count());
        $this->assertSame(1, User::where('username', 'superadmin')->count());
        $this->assertSame(0, Categoria::count());
    }

    public function test_al_crear_un_negocio_por_fabrica_no_se_generan_categorias_ficticias(): void
    {
        $negocio = Negocio::factory()->create();

        $this->assertSame(0, $negocio->categorias()->count());
    }
}
