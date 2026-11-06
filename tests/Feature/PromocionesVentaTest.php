<?php

namespace Tests\Feature;

use App\Livewire\Ventas\PantallaPrincipal;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromocionesVentaTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function productoDe(Negocio $negocio, string $nombre, float $precio, ?int $stock = null): Producto
    {
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        return Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => $precio,
            'stock' => $stock,
        ]);
    }

    /**
     * @param  array<int, array{0: Producto, 1: int}>  $items
     * @param  array<string, mixed>  $atributos
     */
    private function promocionDe(Negocio $negocio, array $items, array $atributos = []): Promocion
    {
        $promocion = Promocion::factory()->create(array_merge([
            'negocio_id' => $negocio->id,
            'nombre' => 'Combo de prueba',
            'precio' => 18,
            'fecha_inicio' => now()->startOfDay()->toDateString(),
            'fecha_fin' => now()->addWeek()->toDateString(),
            'dias_semana' => [1, 2, 3, 4, 5, 6, 7],
            'activa' => true,
        ], $atributos));

        $promocion->productos()->attach(
            collect($items)->mapWithKeys(fn (array $item): array => [
                $item[0]->id => ['cantidad' => $item[1]],
            ])->all()
        );

        return $promocion;
    }

    public function test_las_promociones_vigentes_aparecen_y_se_agregan_al_detalle(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $flan = $this->productoDe($negocio, 'Flan', 5);
        $promocion = $this->promocionDe($negocio, [[$cafe, 1], [$flan, 1]], ['precio' => 12]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertSee('Promociones de hoy')
            ->assertSee('Combo de prueba')
            ->assertSee('Incluye:')
            ->assertSee('Ahorras Bs 3.00')
            ->call('agregarPromocion', $promocion->id)
            ->assertSet('carritoPromociones', [$promocion->id => 1])
            ->assertSee('Bs 12.00')
            ->assertSee('por combo');
    }

    public function test_las_promociones_inactivas_vencidas_programadas_o_fuera_de_dia_no_aparecen(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);

        $this->promocionDe($negocio, [[$cafe, 1]], ['nombre' => 'Combo inactivo', 'activa' => false]);
        $this->promocionDe($negocio, [[$cafe, 1]], ['nombre' => 'Combo vencido', 'fecha_inicio' => now()->subWeek()->toDateString(), 'fecha_fin' => now()->subDay()->toDateString()]);
        $this->promocionDe($negocio, [[$cafe, 1]], ['nombre' => 'Combo programado', 'fecha_inicio' => now()->addDay()->toDateString(), 'fecha_fin' => now()->addWeek()->toDateString()]);
        $this->promocionDe($negocio, [[$cafe, 1]], ['nombre' => 'Combo de otro día', 'dias_semana' => [1]]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->assertDontSee('Combo inactivo')
            ->assertDontSee('Combo vencido')
            ->assertDontSee('Combo programado')
            ->assertDontSee('Combo de otro día');
    }

    public function test_se_puede_cobrar_una_promocion_y_se_descuenta_el_stock_de_sus_componentes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10, 10);
        $flan = $this->productoDe($negocio, 'Flan', 5, 10);
        $promocion = $this->promocionDe($negocio, [[$cafe, 2], [$flan, 1]], ['nombre' => 'Combo café']);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarPromocion', $promocion->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('carritoPromociones', []);

        $venta = Venta::query()->firstOrFail();

        $this->assertSame('18.00', $venta->total);
        $this->assertSame('promocion', $venta->tipoReporte());
        $this->assertCount(1, $venta->detalles);

        $detalle = $venta->detalles->first();

        $this->assertTrue($detalle->es_promocion);
        $this->assertNull($detalle->producto_id);
        $this->assertSame($promocion->id, $detalle->promocion_id);
        $this->assertSame('Combo café', $detalle->nombre);
        $this->assertSame(1, $detalle->cantidad);
        $this->assertCount(2, $detalle->promocionItems);
        $this->assertDatabaseHas('venta_detalle_promocion_items', [
            'venta_detalle_id' => $detalle->id,
            'producto_id' => $cafe->id,
            'nombre' => 'Café',
            'cantidad' => 2,
        ]);
        $this->assertDatabaseHas('venta_detalle_promocion_items', [
            'venta_detalle_id' => $detalle->id,
            'producto_id' => $flan->id,
            'nombre' => 'Flan',
            'cantidad' => 1,
        ]);

        $this->assertSame(8, $cafe->fresh()->stock);
        $this->assertSame(9, $flan->fresh()->stock);
        $this->assertSame(1, $venta->detalles->sum('cantidad'));
    }

    public function test_una_venta_mixta_suma_productos_normales_y_promociones_y_agrupa_el_stock(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10, 20);
        $flan = $this->productoDe($negocio, 'Flan', 5, 10);
        $promocion = $this->promocionDe($negocio, [[$cafe, 2], [$flan, 1]], ['nombre' => 'Combo café']);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarProducto', $cafe->id)
            ->call('agregarPromocion', $promocion->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors();

        $venta = Venta::query()->firstOrFail();

        $this->assertSame('28.00', $venta->total);
        $this->assertSame('mixta', $venta->tipoReporte());
        $this->assertSame('Combo café', $venta->promocionesNombres());
        $this->assertCount(2, $venta->detalles);
        $this->assertSame(2, $venta->detalles->sum('cantidad'));
        $this->assertSame(17, $cafe->fresh()->stock);
        $this->assertSame(9, $flan->fresh()->stock);
    }

    public function test_al_cobrar_se_quita_una_promocion_que_dejo_de_estar_vigente(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $promocion = $this->promocionDe($negocio, [[$cafe, 1]]);

        $componente = Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarPromocion', $promocion->id);

        $promocion->update(['activa' => false]);

        $componente
            ->call('cobrar')
            ->assertHasErrors('carrito')
            ->assertSet('carritoPromociones', []);

        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_no_se_puede_agregar_una_promocion_de_otro_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $otroNegocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafeAjeno = $this->productoDe($otroNegocio, 'Café ajeno', 10);
        $promocionAjena = $this->promocionDe($otroNegocio, [[$cafeAjeno, 1]]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarPromocion', $promocionAjena->id)
            ->assertNotFound();
    }

    public function test_el_ticket_muestra_la_promocion_y_sus_componentes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $flan = $this->productoDe($negocio, 'Flan', 5);
        $promocion = $this->promocionDe($negocio, [[$cafe, 2], [$flan, 1]], ['nombre' => 'Combo café']);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarPromocion', $promocion->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar');

        $venta = Venta::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertSee('Promoción')
            ->assertSee('Incluye:')
            ->assertSee('2× Café')
            ->assertSee('1× Flan');
    }

    public function test_una_promocion_puede_dejar_stock_negativo_y_lo_avisa(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10, 1);
        $promocion = $this->promocionDe($negocio, [[$cafe, 2]]);

        Livewire::actingAs($admin)
            ->test(PantallaPrincipal::class)
            ->call('agregarPromocion', $promocion->id)
            ->call('abrirModalPago')
            ->call('seleccionarMetodoPago', 'qr')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSee('Atención: stock negativo en Café (-1).');

        $this->assertSame(-1, $cafe->fresh()->stock);
    }
}
