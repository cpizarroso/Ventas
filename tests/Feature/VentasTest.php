<?php

namespace Tests\Feature;

use App\Enums\MetodoPago;
use App\Livewire\Ventas\PantallaPrincipal;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);

        Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'fecha_apertura' => now(),
        ]);

        return $admin;
    }

    private function cajeroDe(Negocio $negocio): User
    {
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajero->id,
            'fecha_apertura' => now(),
        ]);

        return $cajero;
    }

    public function test_la_pantalla_muestra_todos_los_productos_y_los_agrega_al_detalle(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        $postres = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Postres']);

        $limonada = Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);
        $flan = Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSee('Jarra limonada')
            ->assertSee('Flan')
            ->assertSee('Bebidas')
            ->assertSee('Postres')
            ->assertSee('Bs 15.00')
            ->call('agregarProducto', $limonada->id)
            ->assertSet('carrito', [$limonada->id => 1])
            ->assertSee('Detalle de la venta')
            ->assertSee('Pagar')
            ->call('agregarProducto', $flan->id)
            ->assertSet('carrito.'.$flan->id, 1)
            ->assertSee('Bs 20.00');
    }

    public function test_se_puede_cambiar_entre_vista_de_cuadricula_y_lista(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSet('vista', 'grid')
            ->call('cambiarVista', 'lista')
            ->assertSet('vista', 'lista')
            ->call('cambiarVista', 'grid')
            ->assertSet('vista', 'grid');
    }

    public function test_el_buscador_filtra_por_nombre_de_producto_o_categoria(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        $postres = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Postres']);

        Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);
        Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->set('busqueda', 'limonada')
            ->assertSee('Jarra limonada')
            ->assertDontSee('Flan')
            ->set('busqueda', 'Postres')
            ->assertSee('Flan')
            ->assertDontSee('Jarra limonada');
    }

    public function test_las_categorias_filtran_los_productos(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);
        $postres = Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Postres']);

        Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);
        Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionarCategoriaFiltro', $bebidas->id)
            ->assertSet('categoriaFiltroId', $bebidas->id)
            ->assertSee('Jarra limonada')
            ->assertDontSee('Flan')
            ->call('seleccionarCategoriaFiltro', null)
            ->assertSet('categoriaFiltroId', null)
            ->assertSee('Flan');
    }

    public function test_agregar_el_mismo_producto_varias_veces_suma_las_cantidades(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Pacumuto',
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('agregarProducto', $producto->id)
            ->assertSet('carrito', [$producto->id => 2])
            ->assertSee('Bs 20.00');
    }

    public function test_el_detalle_permite_modificar_cantidades_y_quitar_items(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $limonada = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);
        $flan = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $limonada->id)
            ->call('agregarProducto', $flan->id)
            ->call('incrementarCarrito', $limonada->id)
            ->assertSet('carrito.'.$limonada->id, 2)
            ->assertSet('carrito.'.$flan->id, 1)
            ->assertSee('Bs 35.00')
            ->call('disminuirCarrito', $limonada->id)
            ->assertSet('carrito.'.$limonada->id, 1)
            ->call('quitarDelCarrito', $flan->id)
            ->assertSet('carrito', [$limonada->id => 1])
            ->assertSee('Bs 15.00');
    }

    public function test_vaciar_el_detalle_limpia_el_carrito_y_el_pago(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '20.00')
            ->set('glosa', 'Para llevar')
            ->set('cliente', 'Juan Pérez')
            ->set('paraLlevar', true)
            ->call('vaciarCarrito')
            ->assertSet('carrito', [])
            ->assertSet('mostrarModalPago', false)
            ->assertSet('metodoPago', null)
            ->assertSet('montoRecibido', null)
            ->assertSet('glosa', '')
            ->assertSet('cliente', '')
            ->assertSet('paraLlevar', false)
            ->assertSee('El detalle está vacío');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_pagar_abre_y_cierra_el_modal_de_pago(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->assertSet('mostrarModalPago', true)
            ->assertSee('Cobrar venta')
            ->assertSee('Total a pagar')
            ->assertSee('Efectivo')
            ->assertSee('QR')
            ->assertSee('Glosa')
            ->assertSee('Cliente')
            ->call('cerrarModalPago')
            ->assertSet('mostrarModalPago', false);
    }

    public function test_no_se_puede_abrir_el_pago_con_el_detalle_vacio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('abrirModalPago')
            ->assertHasErrors('carrito')
            ->assertSet('mostrarModalPago', false);
    }

    public function test_cobrar_registra_una_sola_venta_con_todos_los_items_y_limpia_el_detalle(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $bebidas = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $postres = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $limonada = Producto::factory()->create([
            'categoria_id' => $bebidas->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);
        $flan = Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Flan',
            'precio' => 5,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $limonada->id)
            ->call('agregarProducto', $limonada->id)
            ->call('agregarProducto', $flan->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('mostrarModalPago', false)
            ->assertSet('carrito', [])
            ->assertSet('metodoPago', null)
            ->assertSet('montoRecibido', null);

        $this->assertDatabaseCount('ventas', 1);

        $venta = Venta::query()->where('negocio_id', $negocio->id)->firstOrFail();

        $this->assertSame($admin->id, $venta->user_id);
        $this->assertSame('35.00', $venta->total);
        $this->assertSame(MetodoPago::Qr, $venta->metodo_pago);
        $this->assertSame('35.00', $venta->monto_recibido);
        $this->assertSame('0.00', $venta->cambio);
        $this->assertNull($venta->glosa);
        $this->assertCount(2, $venta->detalles);

        $caja = Caja::query()->where('user_id', $admin->id)->firstOrFail();

        $this->assertSame($caja->id, $venta->caja_id);
        $this->assertSame(1, $caja->fresh()->cantidad_ventas);
        $this->assertSame('35.00', $caja->fresh()->total_ventas);
        $this->assertSame('35.00', $caja->fresh()->total_qr);
        $this->assertSame('0.00', $caja->fresh()->total_efectivo);

        $this->assertDatabaseHas('venta_detalles', [
            'venta_id' => $venta->id,
            'producto_id' => $limonada->id,
            'nombre' => 'Jarra limonada',
            'precio_unitario' => 15,
            'cantidad' => 2,
            'subtotal' => 30,
        ]);

        $this->assertDatabaseHas('venta_detalles', [
            'venta_id' => $venta->id,
            'producto_id' => $flan->id,
            'nombre' => 'Flan',
            'precio_unitario' => 5,
            'cantidad' => 1,
            'subtotal' => 5,
        ]);
    }

    public function test_la_glosa_general_se_guarda_con_la_venta(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->set('glosa', '  Para llevar  ')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::query()->firstOrFail();

        $this->assertSame('Para llevar', $venta->glosa);
        $this->assertNull($venta->detalles()->firstOrFail()->glosa);
    }

    public function test_una_glosa_de_mas_de_255_caracteres_bloquea_el_cobro(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->set('glosa', str_repeat('a', 256))
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasErrors('glosa')
            ->assertSet('carrito', [$producto->id => 1])
            ->assertSet('mostrarModalPago', true);

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_el_cliente_opcional_se_guarda_con_la_venta(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->set('cliente', '  Juan Pérez  ')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('cliente', '');

        $venta = Venta::query()->firstOrFail();

        $this->assertSame('Juan Pérez', $venta->cliente);
    }

    public function test_el_cobro_guarda_si_la_venta_es_para_llevar(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->assertSee('Para llevar')
            ->assertSee('La comida no se consumirá dentro del local.')
            ->set('paraLlevar', true)
            ->assertSee('No se consume en el local.')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('paraLlevar', false);

        $venta = Venta::query()->firstOrFail();

        $this->assertTrue($venta->para_llevar);
    }

    public function test_la_venta_no_es_para_llevar_por_defecto(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSet('paraLlevar', false)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::query()->firstOrFail();

        $this->assertFalse($venta->para_llevar);
    }

    public function test_un_cliente_de_mas_de_120_caracteres_bloquea_el_cobro(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->set('cliente', str_repeat('a', 121))
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasErrors('cliente')
            ->assertSet('carrito', [$producto->id => 1]);

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_el_pago_en_efectivo_registra_el_monto_recibido_y_el_cambio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '20,00')
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::query()->firstOrFail();

        $this->assertSame(MetodoPago::Efectivo, $venta->metodo_pago);
        $this->assertSame('15.00', $venta->total);
        $this->assertSame('20.00', $venta->monto_recibido);
        $this->assertSame('5.00', $venta->cambio);
    }

    public function test_el_pago_en_efectivo_insuficiente_bloquea_el_cobro(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 15,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '10.00')
            ->call('cobrar')
            ->assertHasErrors('montoRecibido')
            ->assertSet('mostrarModalPago', true)
            ->assertSet('carrito', [$producto->id => 1]);

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_cambiar_a_qr_limpia_el_efectivo_recibido(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '20.00')
            ->call('seleccionarMetodoPago', 'qr')
            ->assertSet('montoRecibido', null);
    }

    public function test_el_cambio_se_recalcula_al_modificar_las_cantidades_del_detalle(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '20.00')
            ->assertSee('Bs 5.00')
            ->call('incrementarCarrito', $producto->id)
            ->assertSee('El efectivo recibido no cubre el total')
            ->call('disminuirCarrito', $producto->id)
            ->assertSee('Bs 5.00');
    }

    public function test_cobrar_descuenta_el_stock_de_los_items_limitados_y_no_de_los_ilimitados(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $limitado = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
            'stock' => 5,
        ]);
        $ilimitado = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Flan',
            'precio' => 5,
            'stock' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $limitado->id)
            ->call('agregarProducto', $limitado->id)
            ->call('agregarProducto', $ilimitado->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $this->assertSame(3, $limitado->fresh()->stock);
        $this->assertNull($ilimitado->fresh()->stock);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_cobrar_mas_unidades_que_el_stock_deja_stock_negativo_y_lo_avisa(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
            'stock' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSee('Atención: stock negativo en Jarra limonada (-1).');

        $this->assertSame(-1, $producto->fresh()->stock);
        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_la_pantalla_muestra_el_stock_y_avisa_cuando_se_supera(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $agotado = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Agotado',
            'precio' => 10,
            'stock' => 0,
        ]);
        Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Bajo stock',
            'precio' => 10,
            'stock' => 4,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('cambiarVista', 'lista')
            ->assertSee('Stock: 0')
            ->assertSee('Agotado')
            ->assertSee('Stock: 4')
            ->assertSee('Bajo stock')
            ->call('agregarProducto', $agotado->id)
            ->call('agregarProducto', $agotado->id)
            ->assertSet('carrito', [$agotado->id => 2])
            ->assertSee('Supera el stock; quedarán -2 unidades')
            ->assertSee('Algunos ítems superan el stock disponible');
    }

    public function test_tras_cobrar_se_ofrece_imprimir_el_ticket_de_forma_opcional(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        $componente = Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::query()->firstOrFail();

        $componente
            ->assertSet('ventaParaImprimirId', $venta->id)
            ->assertSee('¿Deseas imprimir el detalle de esta venta?')
            ->assertSee(route('ventas.ticket', ['venta' => $venta->id, 'imprimir' => 1]), false)
            ->call('omitirImpresion')
            ->assertSet('ventaParaImprimirId', null);

        $this->assertDatabaseCount('ventas', 1);
    }

    public function test_no_se_puede_cobrar_con_el_detalle_vacio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_no_se_puede_cobrar_sin_una_caja_abierta(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSee('No tienes una caja abierta.')
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->assertHasErrors('carrito')
            ->assertSet('mostrarModalPago', false)
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_el_numero_de_ticket_se_reinicia_cada_dia(): void
    {
        $this->travelTo('2026-10-22 12:00:00');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 10,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $this->travelTo('2026-10-23 12:00:00');

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $this->assertSame([1, 2], Venta::query()
            ->whereDate('fecha_ticket', '2026-10-22')
            ->orderBy('id')
            ->pluck('numero_ticket')
            ->all());

        $this->assertSame([1], Venta::query()
            ->whereDate('fecha_ticket', '2026-10-23')
            ->pluck('numero_ticket')
            ->all());
    }

    public function test_no_se_pueden_cobrar_productos_de_otro_negocio_aunque_se_manipule_el_estado(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaAjena = Categoria::factory()->create(['negocio_id' => $negocioAjeno->id]);
        $productoAjeno = Producto::factory()->create(['categoria_id' => $categoriaAjena->id]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->set('carrito', [$productoAjeno->id => 1])
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_un_cajero_puede_agregar_y_cobrar_y_la_venta_queda_a_su_nombre(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Coca Cola',
            'precio' => 8,
        ]);

        Livewire::actingAs($cajero)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::query()->firstOrFail();

        $this->assertSame($cajero->id, $venta->user_id);
        $this->assertSame($negocio->id, $venta->negocio_id);
        $this->assertSame('8.00', $venta->total);
        $this->assertSame(MetodoPago::Qr, $venta->metodo_pago);
    }

    public function test_el_detalle_conserva_nombre_y_precio_aunque_el_producto_cambie_o_se_elimine(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Jarra limonada',
            'precio' => 15,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar');

        $detalle = VentaDetalle::query()->firstOrFail();

        $producto->update(['nombre' => 'Limonada premium', 'precio' => 99]);

        $detalle->refresh();

        $this->assertSame('Jarra limonada', $detalle->nombre);
        $this->assertSame('15.00', $detalle->precio_unitario);

        $producto->delete();

        $detalle->refresh();

        $this->assertNull($detalle->producto_id);
        $this->assertSame('Jarra limonada', $detalle->nombre);
        $this->assertSame('15.00', $detalle->precio_unitario);
        $this->assertSame(1, $detalle->cantidad);
    }

    public function test_al_elegir_qr_se_muestra_la_imagen_configurada(): void
    {
        $negocio = Negocio::factory()->create(['qr_pago' => 'qrs/pago.png']);
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 12,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->assertSee('storage/qrs/pago.png', false)
            ->assertSee('Ampliar QR de pago', false)
            ->assertSee('Toca la imagen para ampliarla')
            ->assertSee('Escanea el QR para pagar');
    }

    public function test_al_elegir_qr_sin_configuracion_muestra_aviso_y_permite_cobrar(): void
    {
        $negocio = Negocio::factory()->create(['qr_pago' => null]);
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'precio' => 12,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $producto->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->assertSee('Este negocio no tiene un QR configurado')
            ->call('cobrar')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('ventas', 1);
    }
}
