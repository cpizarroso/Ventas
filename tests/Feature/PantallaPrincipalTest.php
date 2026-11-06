<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\CategoriasAdmin;
use App\Livewire\Ventas\PantallaPrincipal;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PantallaPrincipalTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function cajeroDe(Negocio $negocio): User
    {
        return User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
    }

    public function test_las_categorias_del_negocio_aparecen_en_la_pantalla_principal(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Panadería']);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Bebidas')
            ->assertSee('Panadería');
    }

    public function test_el_logo_de_la_cabecera_enlaza_a_la_pantalla_principal(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('aria-label="Ir a la pantalla principal de ventas"', false)
            ->assertSee('href="'.route('ventas.index').'"', false);
    }

    public function test_la_cabecera_muestra_el_enlace_ventas_activo_para_cajeros(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);

        $this->actingAs($cajero)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="'.route('ventas.index').'"', false);
    }

    public function test_el_navbar_muestra_el_nombre_del_usuario_autenticado(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $otroAdmin = User::factory()->admin()->create([
            'negocio_id' => $negocio->id,
            'name' => 'Usuario Ajeno',
        ]);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertDontSee($otroAdmin->name);
    }

    public function test_las_categorias_inactivas_no_aparecen_en_la_pantalla_principal(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        Categoria::factory()->inactiva()->create(['negocio_id' => $negocio->id, 'nombre' => 'Ocultas']);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Bebidas')
            ->assertDontSee('Ocultas');
    }

    public function test_un_negocio_sin_categorias_muestra_el_estado_vacio_para_admins(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('No hay productos para mostrar')
            ->assertSee('Ir a configuración');
    }

    public function test_un_negocio_sin_categorias_muestra_mensaje_informativo_para_cajeros(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);

        $this->actingAs($cajero)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('No hay productos para mostrar')
            ->assertDontSee('Ir a configuración');
    }

    public function test_una_categoria_nueva_creada_por_el_admin_aparece_en_la_pantalla_principal(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Lácteos')
            ->call('crear');

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Lácteos');
    }

    public function test_el_filtro_de_categoria_muestra_solo_sus_items(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);
        $postres = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Postres',
        ]);
        Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Pacumuto',
            'precio' => 10,
        ]);
        Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionarCategoriaFiltro', $bebidas->id)
            ->assertSee('Pacumuto')
            ->assertSee('Bs 10.00')
            ->assertDontSee('Flan')
            ->call('seleccionarCategoriaFiltro', null)
            ->assertSee('Flan');
    }

    public function test_los_productos_se_ordenan_por_categoria_y_luego_por_nombre(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $postres = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Postres',
            'orden' => 1,
        ]);
        $bebidas = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
            'orden' => 2,
        ]);
        Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Agua',
        ]);
        Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Cafe',
        ]);
        Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
        ]);
        Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Torta',
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSeeInOrder(['Flan', 'Torta', 'Agua', 'Cafe'])
            ->call('seleccionarCategoriaFiltro', $bebidas->id)
            ->assertSeeInOrder(['Agua', 'Cafe']);
    }

    public function test_los_contadores_actualizan_las_cantidades_y_el_total(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $limonada = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);
        $refresco = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Refresco',
            'precio' => 8.50,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSet('carrito', [])
            ->call('agregarProducto', $limonada->id)
            ->call('agregarProducto', $refresco->id)
            ->call('incrementarCarrito', $limonada->id)
            ->assertSet('carrito.'.$limonada->id, 2)
            ->assertSet('carrito.'.$refresco->id, 1)
            ->assertSee('Bs 38.50')
            ->call('disminuirCarrito', $limonada->id)
            ->assertSet('carrito.'.$limonada->id, 1)
            ->assertSee('Bs 23.50');
    }

    public function test_las_cantidades_no_bajan_de_cero(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Budin',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('disminuirCarrito', $producto->id)
            ->assertSet('carrito', [])
            ->assertSee('Bs 0.00')
            ->call('agregarProducto', $producto->id)
            ->call('disminuirCarrito', $producto->id)
            ->call('disminuirCarrito', $producto->id)
            ->assertSet('carrito', []);
    }

    public function test_el_carrito_se_conserva_al_cambiar_el_filtro_de_categoria(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        $postres = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Postres']);
        $producto = Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->assertSet('carrito', [$producto->id => 1])
            ->call('seleccionarCategoriaFiltro', $postres->id)
            ->assertSet('carrito', [$producto->id => 1])
            ->assertSee('Bs 15.00');
    }

    public function test_no_se_puede_agregar_un_producto_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaAjena = Categoria::factory()->create(['negocio_id' => $negocioAjeno->id]);
        $productoAjeno = Producto::factory()->create(['categoria_id' => $categoriaAjena->id]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $productoAjeno->id)
            ->assertNotFound();
    }

    public function test_una_categoria_sin_items_muestra_el_estado_vacio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionarCategoriaFiltro', $categoria->id)
            ->assertSee('No hay productos para mostrar');
    }

    public function test_no_se_puede_filtrar_por_una_categoria_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaAjena = Categoria::factory()->create([
            'negocio_id' => $negocioAjeno->id,
            'nombre' => 'Bebidas',
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionarCategoriaFiltro', $categoriaAjena->id)
            ->assertNotFound();
    }

    public function test_las_categorias_se_muestran_solo_del_negocio_del_usuario_autenticado(): void
    {
        $negocioA = Negocio::factory()->create();
        $negocioB = Negocio::factory()->create();
        $adminA = $this->adminDe($negocioA);
        $this->adminDe($negocioB);
        Categoria::factory()->create(['negocio_id' => $negocioA->id, 'nombre' => 'CategoriaA']);
        Categoria::factory()->create(['negocio_id' => $negocioB->id, 'nombre' => 'CategoriaB']);

        Livewire::actingAs($adminA)
            ->test(PantallaPrincipal::class)
            ->assertSee('CategoriaA')
            ->assertDontSee('CategoriaB');
    }

    public function test_los_cajeros_pueden_ver_y_filtrar_categorias(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);
        $categoria = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);
        Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Coca Cola',
            'precio' => 8,
        ]);

        Livewire::actingAs($cajero)
            ->test(PantallaPrincipal::class)
            ->assertSee('Bebidas')
            ->call('seleccionarCategoriaFiltro', $categoria->id)
            ->assertSee('Coca Cola')
            ->assertSee('Bs 8.00');
    }

    public function test_un_usuario_sin_negocio_ve_un_mensaje_informativo(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        Livewire::actingAs($superadmin)
            ->test(PantallaPrincipal::class)
            ->assertSee('Tu usuario no tiene un negocio asignado');
    }

    public function test_la_imagen_del_producto_aparece_en_grid_lista_y_detalle(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/cafe.jpg', 'contenido');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Café',
            'imagen' => 'productos/cafe.jpg',
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSee('productos/cafe.jpg', false)
            ->call('agregarProducto', $producto->id)
            ->assertSee('productos/cafe.jpg', false)
            ->call('cambiarVista', 'lista')
            ->assertSee('productos/cafe.jpg', false);
    }

    public function test_las_categorias_no_estan_hardcodeadas_y_se_obtienen_de_la_base_de_datos(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'NombreCustom123']);

        $conCategorias = $this->actingAs($admin)->get(route('ventas.index'));
        $conCategorias->assertOk()->assertSee('NombreCustom123');

        $otroAdmin = $this->adminDe(Negocio::factory()->create());
        $sinCategorias = $this->actingAs($otroAdmin)->get(route('ventas.index'));
        $sinCategorias->assertOk()->assertDontSee('NombreCustom123');
    }
}
