<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\CategoriasAdmin;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ProductosAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function abrirCategoria(User $admin, Categoria $categoria): Testable
    {
        return Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('abrirProductos', $categoria->id)
            ->assertSet('productosAbiertosId', $categoria->id);
    }

    public function test_las_categorias_creadas_aparecen_con_la_opcion_de_items(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->assertSee('Bebidas')
            ->assertSee('Ítems');
    }

    public function test_un_admin_puede_crear_un_item_con_nombre_y_precio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café con leche')
            ->set('productoPrecio', '75.50')
            ->call('crearProducto')
            ->assertHasNoErrors()
            ->assertSet('productoNombre', '')
            ->assertSet('productoPrecio', '');

        $this->assertDatabaseHas('productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Café con leche',
            'precio' => 75.50,
        ]);
    }

    public function test_un_admin_puede_modificar_y_eliminar_un_item(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Café',
            'precio' => 50,
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->set('productoNombreEdicion', 'Café americano')
            ->set('productoPrecioEdicion', '55.25')
            ->call('guardarEdicionProducto')
            ->assertHasNoErrors()
            ->assertSet('productoEditandoId', null);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Café americano',
            'precio' => 55.25,
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('confirmarEliminacionProducto', $producto->id)
            ->assertSet('productoEliminandoId', $producto->id)
            ->call('eliminarProducto')
            ->assertSet('productoEliminandoId', null);

        $this->assertModelMissing($producto);
    }

    public function test_se_rechazan_items_sin_nombre_o_con_precio_invalido(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', '')
            ->set('productoPrecio', '')
            ->call('crearProducto')
            ->assertHasErrors(['productoNombre' => 'required', 'productoPrecio' => 'required']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '-5')
            ->call('crearProducto')
            ->assertHasErrors(['productoPrecio' => 'min']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10.999')
            ->call('crearProducto')
            ->assertHasErrors(['productoPrecio' => 'decimal']);

        $this->assertSame(0, $categoria->productos()->count());
    }

    public function test_se_rechaza_un_item_duplicado_dentro_de_la_misma_categoria(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'nombre' => 'Café']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->call('crearProducto')
            ->assertHasErrors(['productoNombre']);

        $this->assertSame(1, $categoria->productos()->count());
    }

    public function test_un_cajero_no_puede_gestionar_items(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('abrirProductos', $categoria->id)
            ->assertForbidden();

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicionProducto', $producto->id)
            ->assertForbidden();

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacionProducto', $producto->id)
            ->assertForbidden();

        $this->assertModelExists($producto);
    }

    public function test_un_admin_no_puede_gestionar_items_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaAjena = Categoria::factory()->create(['negocio_id' => $negocioAjeno->id]);
        $productoAjeno = Producto::factory()->create(['categoria_id' => $categoriaAjena->id]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('abrirProductos', $categoriaAjena->id)
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicionProducto', $productoAjeno->id)
            ->assertNotFound();

        $this->assertModelExists($productoAjeno);
    }

    public function test_al_eliminar_una_categoria_se_eliminan_sus_items(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoria->id)
            ->call('eliminar');

        $this->assertModelMissing($categoria);
        $this->assertModelMissing($producto);
    }
}
