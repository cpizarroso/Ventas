<?php

namespace Tests\Feature;

use App\Enums\MetodoPago;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    /**
     * Create a sale with its detail lines at a specific date and time.
     *
     * @param  array<int, array{0: string, 1: float, 2: int}>  $items
     */
    private function ventaDe(
        Negocio $negocio,
        User $vendedor,
        string $fecha,
        array $items,
        MetodoPago $metodo = MetodoPago::Efectivo,
        ?string $montoRecibido = null,
        ?string $cambio = null,
    ): Venta {
        $total = collect($items)->sum(fn (array $item) => round($item[1] * $item[2], 2));

        $venta = Venta::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $vendedor->id,
            'total' => $total,
            'metodo_pago' => $metodo,
            'monto_recibido' => $montoRecibido,
            'cambio' => $cambio,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);

        foreach ($items as [$nombre, $precio, $cantidad]) {
            $venta->detalles()->create([
                'nombre' => $nombre,
                'precio_unitario' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => round($precio * $cantidad, 2),
            ]);
        }

        return $venta;
    }

    public function test_un_admin_solo_ve_las_ventas_de_su_negocio(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $adminAjeno = $this->adminDe($negocioAjeno);

        $this->ventaDe($negocioPropio, $admin, '2026-10-22 10:00:00', [['ProductoPropio', 10, 1]]);
        $this->ventaDe($negocioAjeno, $adminAjeno, '2026-10-22 11:00:00', [['ProductoAjeno', 20, 1]]);

        $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->assertSee('ProductoPropio')
            ->assertDontSee('ProductoAjeno')
            ->assertSee('Bs 10.00')
            ->assertDontSee('Bs 20.00');
    }

    public function test_el_rango_de_fechas_filtra_las_ventas(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->ventaDe($negocio, $admin, '2026-10-22 10:00:00', [['SoloHoy', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-10-21 10:00:00', [['SoloAyer', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-10-14 10:00:00', [['SoloCatorce', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-10-10 10:00:00', [['SoloDiez', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-10-01 10:00:00', [['SoloPrimero', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-09-20 10:00:00', [['SoloSeptiembre', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-01-05 10:00:00', [['SoloEnero', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2025-12-31 10:00:00', [['SoloDiciembre2025', 10, 1]]);

        $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->assertSee('SoloHoy')
            ->assertDontSee('SoloAyer')
            ->assertDontSee('SoloCatorce');

        $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-10', 'hasta' => '2026-10-14']))
            ->assertOk()
            ->assertSee('SoloDiez')
            ->assertSee('SoloCatorce')
            ->assertDontSee('SoloPrimero')
            ->assertDontSee('SoloHoy');

        $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-01', 'hasta' => '2026-10-31']))
            ->assertOk()
            ->assertSee('SoloPrimero')
            ->assertSee('SoloHoy')
            ->assertDontSee('SoloSeptiembre');

        $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-01-01', 'hasta' => '2026-12-31']))
            ->assertOk()
            ->assertSee('SoloEnero')
            ->assertSee('SoloSeptiembre')
            ->assertDontSee('SoloDiciembre2025');
    }

    public function test_sin_fechas_se_usa_el_mes_actual(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->ventaDe($negocio, $admin, '2026-10-22 10:00:00', [['SoloOctubre', 10, 1]]);
        $this->ventaDe($negocio, $admin, '2026-09-30 10:00:00', [['SoloSeptiembre', 10, 1]]);

        $response = $this->actingAs($admin)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('SoloOctubre')
            ->assertDontSee('SoloSeptiembre');

        $this->assertSame('2026-10-01', $response->viewData('desde')->format('Y-m-d'));
        $this->assertSame('2026-10-31', $response->viewData('hasta')->format('Y-m-d'));
    }

    public function test_un_rango_de_fechas_invalido_muestra_un_error_de_validacion(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->get(route('reportes.index', [
                'desde' => '2026-10-22',
                'hasta' => '2026-10-01',
            ]))
            ->assertSessionHasErrors('hasta');
    }

    public function test_los_totales_y_el_resumen_por_producto_son_correctos(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->ventaDe($negocio, $admin, '2026-10-22 09:00:00', [
            ['Café', 10, 2],
            ['Flan', 5, 1],
        ]);
        $this->ventaDe($negocio, $admin, '2026-10-22 11:00:00', [
            ['Café', 10, 1],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->assertSee('Bs 35.00')
            ->assertSee('Bs 17.50');

        $this->assertSame(35.0, $response->viewData('totalVendido'));
        $this->assertSame(2, $response->viewData('cantidadVentas'));
        $this->assertSame(4, $response->viewData('itemsVendidos'));
        $this->assertSame(17.5, $response->viewData('ticketPromedio'));

        $resumen = $response->viewData('resumenProductos');

        $this->assertCount(2, $resumen);
        $this->assertSame('Café', $resumen[0]->nombre);
        $this->assertSame(3, (int) $resumen[0]->cantidad);
        $this->assertSame(30.0, (float) $resumen[0]->total);
        $this->assertSame('Flan', $resumen[1]->nombre);
        $this->assertSame(5.0, (float) $resumen[1]->total);
    }

    public function test_el_reporte_separa_efectivo_y_qr(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->ventaDe($negocio, $admin, '2026-10-22 09:00:00', [
            ['Café', 20, 1],
        ], MetodoPago::Efectivo, '50.00', '30.00');
        $this->ventaDe($negocio, $admin, '2026-10-22 10:00:00', [
            ['Té', 15, 1],
        ], MetodoPago::Qr, '15.00', '0.00');

        $response = $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->assertSee('Efectivo cobrado')
            ->assertSee('Cobrado por QR')
            ->assertDontSee('Cambio');

        $this->assertSame(20.0, $response->viewData('totalEfectivo'));
        $this->assertSame(15.0, $response->viewData('totalQr'));

        $contenido = $this->actingAs($admin)
            ->get(route('reportes.export', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->streamedContent();

        $this->assertStringContainsString(';20.00;Efectivo;50.00', $contenido);
        $this->assertStringContainsString(';15.00;QR;15.00', $contenido);
    }

    public function test_el_reporte_general_incluye_a_todos_los_cajeros_y_permite_filtrar_por_uno(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cajeroA = User::factory()->cajero()->create(['negocio_id' => $negocio->id, 'name' => 'Ana Cajera']);
        $cajeroB = User::factory()->cajero()->create(['negocio_id' => $negocio->id, 'name' => 'Luis Cajero']);

        $ventaA = $this->ventaDe($negocio, $cajeroA, '2026-10-22 09:00:00', [['ProductoAna', 10, 1]]);
        $ventaB = $this->ventaDe($negocio, $cajeroB, '2026-10-22 10:00:00', [['ProductoLuis', 20, 1]]);

        $general = $this->actingAs($admin)
            ->get(route('reportes.index', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->assertSee('ProductoAna')
            ->assertSee('ProductoLuis');

        $this->assertSame(30.0, $general->viewData('totalVendido'));
        $this->assertSame(2, $general->viewData('cantidadVentas'));
        $this->assertNull($general->viewData('cajeroSeleccionado'));

        $soloAna = $this->actingAs($admin)
            ->get(route('reportes.index', [
                'desde' => '2026-10-22',
                'hasta' => '2026-10-22',
                'cajero_id' => $cajeroA->id,
            ]))
            ->assertOk()
            ->assertSee('ProductoAna')
            ->assertDontSee('ProductoLuis')
            ->assertSee('Cajero:');

        $this->assertSame(10.0, $soloAna->viewData('totalVendido'));
        $this->assertSame(1, $soloAna->viewData('cantidadVentas'));
        $this->assertSame($cajeroA->id, $soloAna->viewData('cajeroSeleccionado')->id);

        $csv = $this->actingAs($admin)
            ->get(route('reportes.export', [
                'desde' => '2026-10-22',
                'hasta' => '2026-10-22',
                'cajero_id' => $cajeroA->id,
            ]))
            ->streamedContent();

        $this->assertStringContainsString($ventaA->id.';22/10/2026;09:00;', $csv);
        $this->assertStringContainsString('Ana Cajera', $csv);
        $this->assertStringNotContainsString($ventaB->id.';22/10/2026;10:00;', $csv);
    }

    public function test_no_se_puede_filtrar_por_un_cajero_de_otro_negocio_o_por_un_admin(): void
    {
        $negocio = Negocio::factory()->create();
        $otroNegocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cajeroAjeno = User::factory()->cajero()->create(['negocio_id' => $otroNegocio->id]);

        $this->actingAs($admin)
            ->get(route('reportes.index', ['cajero_id' => $cajeroAjeno->id]))
            ->assertSessionHasErrors('cajero_id');

        $this->actingAs($admin)
            ->get(route('reportes.index', ['cajero_id' => $admin->id]))
            ->assertSessionHasErrors('cajero_id');
    }

    public function test_se_puede_exportar_el_reporte_filtrado_en_csv(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $ventaHoy = $this->ventaDe($negocio, $admin, '2026-10-22 10:30:00', [
            ['Café', 10, 1],
            ['Flan', 2.5, 1],
        ], MetodoPago::Efectivo, '20.00', '7.50');
        $ventaAyer = $this->ventaDe($negocio, $admin, '2026-10-21 09:00:00', [
            ['Té', 7, 1],
        ]);

        $response = $this->actingAs($admin)
            ->get(route('reportes.export', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $contenido = $response->streamedContent();

        $this->assertStringContainsString('Venta;Fecha;Hora;Vendedor;Ítems;Total;Método;Recibido', $contenido);
        $this->assertStringContainsString($ventaHoy->id.';22/10/2026;10:30;', $contenido);
        $this->assertStringContainsString($admin->name, $contenido);
        $this->assertStringContainsString(';2;12.50;Efectivo;20.00', $contenido);
        $this->assertStringNotContainsString(';21/10/2026;', $contenido);
    }

    public function test_un_cajero_solo_ve_sus_propias_ventas_en_el_reporte(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'name' => 'Ana Cajera',
        ]);
        $otroCajero = User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'name' => 'Luis Cajero',
        ]);

        $this->ventaDe($negocio, $cajero, '2026-10-22 09:00:00', [['ProductoPropio', 10, 1]]);
        $this->ventaDe($negocio, $otroCajero, '2026-10-22 10:00:00', [['ProductoAjeno', 20, 1]]);

        $respuesta = $this->actingAs($cajero)
            ->get(route('reportes.index', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->assertSee('ProductoPropio')
            ->assertDontSee('ProductoAjeno')
            ->assertSee('Ventas de:')
            ->assertSee('Ana Cajera')
            ->assertDontSee('name="cajero_id"', false);

        $this->assertFalse($respuesta->viewData('puedeFiltrarPorCajero'));
        $this->assertSame($cajero->id, $respuesta->viewData('cajeroId'));
    }

    public function test_un_cajero_no_puede_consultar_las_ventas_de_otro_cajero(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
        $otroCajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        $this->ventaDe($negocio, $cajero, '2026-10-22 09:00:00', [['ProductoPropio', 10, 1]]);
        $this->ventaDe($negocio, $otroCajero, '2026-10-22 10:00:00', [['ProductoAjeno', 20, 1]]);

        $this->actingAs($cajero)
            ->get(route('reportes.index', [
                'desde' => '2026-10-22',
                'hasta' => '2026-10-22',
                'cajero_id' => $otroCajero->id,
            ]))
            ->assertOk()
            ->assertSee('ProductoPropio')
            ->assertDontSee('ProductoAjeno');
    }

    public function test_un_cajero_exporta_solo_sus_propias_ventas_en_csv(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'name' => 'Ana Cajera',
        ]);
        $otroCajero = User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'name' => 'Luis Cajero',
        ]);

        $ventaPropia = $this->ventaDe($negocio, $cajero, '2026-10-22 09:00:00', [['Café', 10, 1]]);
        $ventaAjena = $this->ventaDe($negocio, $otroCajero, '2026-10-22 10:00:00', [['Té', 20, 1]]);

        $respuesta = $this->actingAs($cajero)
            ->get(route('reportes.export', [
                'desde' => '2026-10-22',
                'hasta' => '2026-10-22',
                'cajero_id' => $otroCajero->id,
            ]))
            ->assertOk();

        $contenido = $respuesta->streamedContent();

        $this->assertStringContainsString($ventaPropia->id.';22/10/2026;09:00;', $contenido);
        $this->assertStringContainsString('Ana Cajera', $contenido);
        $this->assertStringNotContainsString($ventaAjena->id.';22/10/2026;10:00;', $contenido);
        $this->assertStringNotContainsString('Luis Cajero', $contenido);
    }

    public function test_un_superadmin_sin_negocio_ve_el_mensaje_informativo(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Sin negocio asignado');
    }

    public function test_el_enlace_de_reportes_es_visible_para_admins_y_cajeros(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
        $urlReportes = route('reportes.index');
        $urlConfiguracion = route('configuracion.index');

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('href="'.$urlReportes.'"', false)
            ->assertSee('href="'.$urlConfiguracion.'"', false);

        $this->actingAs($cajero)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('href="'.$urlReportes.'"', false)
            ->assertDontSee('href="'.$urlConfiguracion.'"', false);
    }
}
