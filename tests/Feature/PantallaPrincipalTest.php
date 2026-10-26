<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\CategoriasAdmin;
use App\Livewire\Ventas\PantallaPrincipal;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Todavía no hay categorías creadas')
            ->assertSee('Ir a configuración');
    }

    public function test_un_negocio_sin_categorias_muestra_mensaje_informativo_para_cajeros(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);

        $this->actingAs($cajero)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('Todavía no hay categorías creadas')
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

    public function test_seleccionar_una_categoria_muestra_sus_items_y_el_total(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);
        Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Pacumuto',
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $categoria->id)
            ->assertSee('Bebidas')
            ->assertSee('Pacumuto')
            ->assertSee('Bs 10.00')
            ->assertSee('Total')
            ->call('deseleccionar')
            ->assertDontSee('Pacumuto');
    }

    public function test_solo_se_muestran_los_items_de_la_categoria_seleccionada(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        $postres = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Postres']);

        Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Coca Cola',
            'precio' => 8,
        ]);
        Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $bebidas->id)
            ->assertSee('Coca Cola')
            ->assertDontSee('Flan');
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
            ->call('seleccionar', $categoria->id)
            ->assertSet('cantidades', [])
            ->call('incrementar', $limonada->id)
            ->call('incrementar', $limonada->id)
            ->call('incrementar', $refresco->id)
            ->assertSet('cantidades', [
                $limonada->id => 2,
                $refresco->id => 1,
            ])
            ->assertSee('Bs 38.50')
            ->call('disminuir', $limonada->id)
            ->assertSet('cantidades', [
                $limonada->id => 1,
                $refresco->id => 1,
            ])
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
            ->call('seleccionar', $categoria->id)
            ->call('disminuir', $producto->id)
            ->assertSet('cantidades', [])
            ->assertSee('Bs 0.00')
            ->call('incrementar', $producto->id)
            ->call('disminuir', $producto->id)
            ->call('disminuir', $producto->id)
            ->assertSet('cantidades', []);
    }

    public function test_las_cantidades_se_conservan_al_cambiar_de_categoria(): void
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
            ->call('seleccionar', $bebidas->id)
            ->call('incrementar', $producto->id)
            ->assertSet('cantidades', [$producto->id => 1])
            ->call('seleccionar', $postres->id)
            ->assertSet('cantidades', [$producto->id => 1])
            ->call('deseleccionar')
            ->assertSet('cantidades', [$producto->id => 1])
            ->call('seleccionar', $bebidas->id)
            ->assertSee('Bs 15.00');
    }

    public function test_no_se_puede_incrementar_un_producto_de_otra_categoria_u_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaPropia = Categoria::factory()->create(['negocio_id' => $negocioPropio->id]);
        $otraCategoriaPropia = Categoria::factory()->create(['negocio_id' => $negocioPropio->id]);
        $categoriaAjena = Categoria::factory()->create(['negocio_id' => $negocioAjeno->id]);

        $productoOtraCategoria = Producto::factory()->create(['categoria_id' => $otraCategoriaPropia->id]);
        $productoAjeno = Producto::factory()->create(['categoria_id' => $categoriaAjena->id]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $categoriaPropia->id)
            ->call('incrementar', $productoOtraCategoria->id)
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $categoriaPropia->id)
            ->call('incrementar', $productoAjeno->id)
            ->assertNotFound();
    }

    public function test_una_categoria_sin_items_muestra_el_estado_vacio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $categoria->id)
            ->assertSee('Esta categoría todavía no tiene ítems');
    }

    public function test_no_se_puede_seleccionar_una_categoria_de_otro_negocio(): void
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
            ->call('seleccionar', $categoriaAjena->id)
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

    public function test_las_cajeros_pueden_ver_y_seleccionar_categorias(): void
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
            ->call('seleccionar', $categoria->id)
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
