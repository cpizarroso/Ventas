<?php

namespace Tests\Feature;

use App\Enums\MetodoPago;
use App\Livewire\Ventas\PantallaPrincipal;
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
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function cajeroDe(Negocio $negocio): User
    {
        return User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
    }

    public function test_el_detalle_muestra_los_items_aceptados_de_varias_categorias_y_el_total(): void
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
        $noConsumido = Producto::factory()->create([
            'categoria_id' => $postres->id,
            'nombre' => 'Budin no consumido',
            'precio' => 99,
        ]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $bebidas->id)
            ->call('incrementar', $limonada->id)
            ->call('incrementar', $limonada->id)
            ->call('aceptar')
            ->assertSet('mostrarDetalle', true)
            ->assertSet('carrito', [$limonada->id => 2])
            ->assertSee('Detalle de venta')
            ->assertSee('Seguir agregando')
            ->assertSee('Efectivo')
            ->assertSee('QR')
            ->assertSee('Cobrar')
            ->assertSee('Jarra limonada')
            ->assertDontSee($noConsumido->nombre)
            ->call('volverACategorias')
            ->call('seleccionar', $postres->id)
            ->call('incrementar', $flan->id)
            ->call('aceptar')
            ->assertSet('carrito', [$limonada->id => 2, $flan->id => 1])
            ->assertSee('Jarra limonada')
            ->assertSee('Flan')
            ->assertSee('Bs 15.00 c/u')
            ->assertSee('Bs 30.00')
            ->assertSee('Bs 35.00');
    }

    public function test_aceptar_sin_items_seleccionados_muestra_un_error(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'precio' => 10]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('seleccionar', $categoria->id)
            ->call('aceptar')
            ->assertHasErrors('cantidades')
            ->assertSet('mostrarDetalle', false)
            ->assertSet('carrito', []);
    }

    public function test_aceptar_el_mismo_producto_dos_veces_suma_las_cantidades(): void
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
            ->assertSet('carrito', [$producto->id => 1])
            ->call('volverACategorias')
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $limonada->id)
            ->call('incrementar', $flan->id)
            ->call('aceptar')
            ->call('incrementarCarrito', $limonada->id)
            ->assertSet('carrito', [$flan->id => 1, $limonada->id => 2])
            ->assertSee('Bs 35.00')
            ->call('disminuirCarrito', $limonada->id)
            ->assertSet('carrito', [$flan->id => 1, $limonada->id => 1])
            ->call('quitarDelCarrito', $flan->id)
            ->assertSet('carrito', [$limonada->id => 1])
            ->assertSee('Bs 15.00');
    }

    public function test_cobrar_registra_una_sola_venta_con_todos_los_items_aceptados_y_limpia_el_detalle(): void
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
            ->call('seleccionar', $bebidas->id)
            ->call('incrementar', $limonada->id)
            ->call('incrementar', $limonada->id)
            ->call('aceptar')
            ->call('volverACategorias')
            ->call('seleccionar', $postres->id)
            ->call('incrementar', $flan->id)
            ->call('aceptar')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('mostrarDetalle', false)
            ->assertSet('carrito', [])
            ->assertSet('cantidades', [])
            ->assertSet('metodoPago', null)
            ->assertSet('montoRecibido', null)
            ->assertSet('categoriaSeleccionadaId', null);

        $this->assertDatabaseCount('ventas', 1);

        $venta = Venta::query()->where('negocio_id', $negocio->id)->firstOrFail();

        $this->assertSame($admin->id, $venta->user_id);
        $this->assertSame('35.00', $venta->total);
        $this->assertSame(MetodoPago::Qr, $venta->metodo_pago);
        $this->assertSame('35.00', $venta->monto_recibido);
        $this->assertSame('0.00', $venta->cambio);
        $this->assertCount(2, $venta->detalles);

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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
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
            ->call('verDetalle')
            ->call('cobrar')
            ->assertHasErrors('carrito');

        $this->assertDatabaseCount('ventas', 0);
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

    public function test_un_cajero_puede_aceptar_y_cobrar_y_la_venta_queda_a_su_nombre(): void
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
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

    public function test_vaciar_el_detalle_limpia_el_carrito_pero_mantiene_la_pantalla_abierta(): void
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
            ->assertSet('carrito', [$producto->id => 1])
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '20.00')
            ->call('vaciarCarrito')
            ->assertSet('carrito', [])
            ->assertSet('metodoPago', null)
            ->assertSet('montoRecibido', null)
            ->assertSet('mostrarDetalle', true)
            ->assertSee('El detalle está vacío');

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_el_boton_ver_detalle_aparece_al_volver_a_las_categorias_con_items_aceptados(): void
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
            ->call('incrementar', $producto->id)
            ->call('aceptar')
            ->call('volverACategorias')
            ->assertSet('mostrarDetalle', false)
            ->assertSee('Ver detalle')
            ->assertSee('Bs 5.00')
            ->call('verDetalle')
            ->assertSet('mostrarDetalle', true)
            ->assertSee('Detalle de venta');
    }

    public function test_no_se_puede_cobrar_sin_seleccionar_un_metodo_de_pago(): void
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
            ->call('cobrar')
            ->assertHasErrors('metodoPago')
            ->assertSet('ventaParaImprimirId', null);

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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '10.00')
            ->call('cobrar')
            ->assertHasErrors('montoRecibido')
            ->assertSet('mostrarDetalle', true)
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
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
            ->call('seleccionar', $categoria->id)
            ->call('incrementar', $producto->id)
            ->call('aceptar')
            ->call('seleccionarMetodoPago', 'efectivo')
            ->set('montoRecibido', '20.00')
            ->assertSee('Bs 5.00')
            ->call('incrementarCarrito', $producto->id)
            ->assertSee('El efectivo recibido no cubre el total')
            ->call('disminuirCarrito', $producto->id)
            ->assertSee('Bs 5.00');
    }
}
