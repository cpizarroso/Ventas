<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Database\Seeders\ProduccionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduccionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepara_el_catalogo_sin_crear_usuarios(): void
    {
        $this->seed(ProduccionSeeder::class);

        $this->assertSame(1, Negocio::count());
        $this->assertSame(0, User::count());

        $this->assertSame(
            ['PACUMUTOS', 'REFRESCOS 2Lt', 'REFRESCOS 500ml'],
            Categoria::query()->orderBy('orden')->pluck('nombre')->all()
        );
        $this->assertSame(14, Producto::count());
        $this->assertSame(5, Categoria::query()->where('nombre', 'PACUMUTOS')->firstOrFail()->productos()->count());
        $this->assertSame(4, Categoria::query()->where('nombre', 'REFRESCOS 2Lt')->firstOrFail()->productos()->count());
        $this->assertSame(5, Categoria::query()->where('nombre', 'REFRESCOS 500ml')->firstOrFail()->productos()->count());

        $this->assertSame('12.00', Producto::query()->where('nombre', 'Jiba')->firstOrFail()->precio);
        $this->assertSame('7.00', Producto::query()->where('nombre', 'Agua 500 ml')->firstOrFail()->precio);

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, Caja::count());
    }

    public function test_es_idempotente(): void
    {
        $this->seed(ProduccionSeeder::class);
        $this->seed(ProduccionSeeder::class);

        $this->assertSame(1, Negocio::count());
        $this->assertSame(0, User::count());
        $this->assertSame(3, Categoria::count());
        $this->assertSame(14, Producto::count());
        $this->assertSame(0, Venta::count());
        $this->assertSame(0, Caja::count());
    }

    public function test_purga_datos_de_prueba_y_conserva_los_usuarios(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'email' => 'cajero@jibas.test',
        ]);
        $categoria = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'CATEGORIA DE PRUEBA',
        ]);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);
        $caja = Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajero->id,
        ]);
        $venta = Venta::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajero->id,
            'caja_id' => $caja->id,
        ]);
        VentaDetalle::factory()->create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
        ]);

        $this->seed(ProduccionSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'cajero@jibas.test']);
        $this->assertSame(0, Venta::count());
        $this->assertSame(0, VentaDetalle::count());
        $this->assertSame(0, Caja::count());
        $this->assertDatabaseMissing('categorias', ['nombre' => 'CATEGORIA DE PRUEBA']);
        $this->assertDatabaseMissing('productos', ['nombre' => $producto->nombre]);
    }
}
