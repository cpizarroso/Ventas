<?php

namespace Tests\Feature;

use App\Enums\EstadoCaja;
use App\Enums\MetodoPago;
use App\Models\Caja;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajasTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function ventaEnCaja(Caja $caja, User $vendedor, MetodoPago $metodo, float $total): Venta
    {
        return Venta::factory()->create([
            'negocio_id' => $caja->negocio_id,
            'caja_id' => $caja->id,
            'user_id' => $vendedor->id,
            'total' => $total,
            'metodo_pago' => $metodo,
        ]);
    }

    public function test_un_usuario_puede_abrir_su_caja_con_monto_inicial_y_observaciones(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->post(route('cajas.store'), [
                'monto_inicial' => '100.50',
                'observaciones_apertura' => 'Sencillo para el turno',
            ])
            ->assertRedirect();

        $caja = Caja::query()->firstOrFail();

        $this->assertSame($negocio->id, $caja->negocio_id);
        $this->assertSame($admin->id, $caja->user_id);
        $this->assertSame(EstadoCaja::Abierta, $caja->estado);
        $this->assertSame('100.50', $caja->monto_inicial);
        $this->assertSame('Sencillo para el turno', $caja->observaciones_apertura);
        $this->assertNotNull($caja->fecha_apertura);
        $this->assertNull($caja->fecha_cierre);
    }

    public function test_al_abrir_una_caja_desde_ventas_se_regresa_a_ventas(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->get(route('cajas.create', ['origen' => 'ventas']))
            ->assertOk()
            ->assertSee('name="origen" value="ventas"', false);

        $this->actingAs($admin)
            ->post(route('cajas.store'), [
                'monto_inicial' => '100',
                'origen' => 'ventas',
            ])
            ->assertRedirect(route('ventas.index'))
            ->assertSessionHas('status');
    }

    public function test_la_lista_muestra_el_monto_de_apertura_y_el_total_esperado(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'monto_inicial' => 100,
            'total_ventas' => 35,
            'total_efectivo' => 25,
            'cantidad_ventas' => 2,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee('Monto apertura')
            ->assertSee('Total esperado')
            ->assertSee('Bs 100.00')
            ->assertSee('Bs 125.00');
    }

    public function test_el_boton_de_la_lista_cambia_a_cerrar_caja_cuando_el_usuario_tiene_una_abierta(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee('Abrir caja')
            ->assertDontSee('Cerrar caja');

        $caja = Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee('Cerrar caja')
            ->assertSee(route('cajas.cerrar', $caja), false)
            ->assertDontSee('Abrir caja');
    }

    public function test_no_se_puede_abrir_una_segunda_caja_abierta(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('cajas.store'), ['monto_inicial' => '50'])
            ->assertRedirect(route('cajas.index'))
            ->assertSessionHas('error');

        $this->assertSame(1, Caja::query()->count());
    }

    public function test_al_intentar_abrir_con_una_caja_abierta_se_redirige_a_su_detalle(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $caja = Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('cajas.create'))
            ->assertRedirect(route('cajas.show', $caja));
    }

    public function test_un_cajero_solo_ve_sus_cajas_y_un_admin_ve_las_de_todos(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cajeroA = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
        $cajeroB = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        $cajaA = Caja::factory()->cerrada()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajeroA->id,
            'fecha_apertura' => now(),
        ]);
        $cajaB = Caja::factory()->cerrada()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajeroB->id,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($cajeroA)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee(route('cajas.show', $cajaA), false)
            ->assertDontSee(route('cajas.show', $cajaB), false);

        $this->actingAs($admin)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee(route('cajas.show', $cajaA), false)
            ->assertSee(route('cajas.show', $cajaB), false);
    }

    public function test_el_cierre_calcula_los_totales_el_efectivo_esperado_y_la_diferencia(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $caja = Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'monto_inicial' => 10,
            'fecha_apertura' => now(),
        ]);

        $this->ventaEnCaja($caja, $admin, MetodoPago::Efectivo, 100);
        $this->ventaEnCaja($caja, $admin, MetodoPago::Qr, 50);

        $this->actingAs($admin)
            ->post(route('cajas.cerrar.store', $caja), [
                'monto_cierre' => '115.00',
                'observaciones_cierre' => 'Faltante de sencillo',
            ])
            ->assertRedirect(route('cajas.show', $caja));

        $caja->refresh();

        $this->assertSame(EstadoCaja::Cerrada, $caja->estado);
        $this->assertSame($admin->id, $caja->cerrada_por_id);
        $this->assertNotNull($caja->fecha_cierre);
        $this->assertSame('150.00', $caja->total_ventas);
        $this->assertSame('100.00', $caja->total_efectivo);
        $this->assertSame('50.00', $caja->total_qr);
        $this->assertSame(2, $caja->cantidad_ventas);
        $this->assertSame('110.00', $caja->monto_esperado_efectivo);
        $this->assertSame('115.00', $caja->monto_cierre);
        $this->assertSame('5.00', $caja->diferencia);
        $this->assertSame('Faltante de sencillo', $caja->observaciones_cierre);
    }

    public function test_no_se_puede_cerrar_una_caja_ya_cerrada(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $caja = Caja::factory()->cerrada()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('cajas.cerrar', $caja))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('cajas.cerrar.store', $caja), ['monto_cierre' => '50'])
            ->assertForbidden();
    }

    public function test_no_se_puede_ver_ni_cerrar_una_caja_de_otro_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $otroNegocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $adminAjeno = $this->adminDe($otroNegocio);
        $cajaAjena = Caja::factory()->create([
            'negocio_id' => $otroNegocio->id,
            'user_id' => $adminAjeno->id,
            'fecha_apertura' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('cajas.show', $cajaAjena))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('cajas.cerrar', $cajaAjena))
            ->assertForbidden();
    }

    public function test_la_caja_muestra_sus_ventas_y_los_totales_del_turno(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $caja = Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'monto_inicial' => 20,
            'fecha_apertura' => now(),
        ]);
        $venta = $this->ventaEnCaja($caja, $admin, MetodoPago::Efectivo, 100);
        $venta->update(['numero_ticket' => 8, 'fecha_ticket' => now()->toDateString()]);

        $this->actingAs($admin)
            ->get(route('cajas.show', $caja))
            ->assertOk()
            ->assertSee('Caja #'.$caja->id)
            ->assertSee('#8')
            ->assertSee('Bs 100.00')
            ->assertSee('Bs 120.00')
            ->assertSee('Cerrar caja');
    }

    public function test_el_listado_pagina_las_cajas_de_diez_en_diez_y_filtra_por_estado(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        for ($i = 0; $i < 12; $i++) {
            Caja::factory()->cerrada()->create([
                'negocio_id' => $negocio->id,
                'user_id' => $admin->id,
                'fecha_apertura' => now()->subMinutes($i),
                'fecha_cierre' => now(),
            ]);
        }

        $abierta = Caja::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'fecha_apertura' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('cajas.index'))
            ->assertOk();

        $this->assertSame(10, $response->viewData('cajas')->count());
        $this->assertSame(13, $response->viewData('cajas')->total());

        $filtradas = $this->actingAs($admin)
            ->get(route('cajas.index', ['estado' => 'abierta']))
            ->assertOk();

        $this->assertSame(1, $filtradas->viewData('cajas')->total());
        $this->assertSame('#'.$abierta->id, '#'.$filtradas->viewData('cajas')->first()->id);
    }

    public function test_se_puede_exportar_el_reporte_de_cajas_en_csv(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $caja = Caja::factory()->cerrada()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $admin->id,
            'cerrada_por_id' => $admin->id,
            'fecha_apertura' => now(),
            'fecha_cierre' => now(),
            'monto_inicial' => 10,
            'total_ventas' => 150,
            'total_efectivo' => 100,
            'total_qr' => 50,
            'cantidad_ventas' => 2,
            'monto_esperado_efectivo' => 110,
            'monto_cierre' => 115,
            'diferencia' => 5,
            'observaciones_cierre' => 'Faltante',
        ]);

        $contenido = $this->actingAs($admin)
            ->get(route('cajas.export', ['desde' => '2026-10-22', 'hasta' => '2026-10-22']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Caja;Estado;"Abierta por";"Cerrada por";Apertura;Cierre;"Monto inicial";Ventas;"Total vendido";Efectivo;QR;"Efectivo esperado";"Efectivo contado";Diferencia;"Observaciones apertura";"Observaciones cierre"', $contenido);
        $this->assertStringContainsString($caja->id.';Cerrada;', $contenido);
        $this->assertStringContainsString($admin->name, $contenido);
        $this->assertStringContainsString(';10.00;2;150.00;100.00;50.00;110.00;115.00;5.00;;Faltante', $contenido);
    }

    public function test_un_superadmin_sin_negocio_ve_el_mensaje_informativo(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)
            ->get(route('cajas.index'))
            ->assertOk()
            ->assertSee('Sin negocio asignado');
    }
}
