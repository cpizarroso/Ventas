<?php

namespace Tests\Feature;

use App\Enums\EstadoCaja;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\CarbonImmutable;
use Database\Seeders\ProduccionSeeder;
use Database\Seeders\ReportePruebasSeeder;
use Database\Seeders\SuperadminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportePruebasSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_500_ventas_y_una_caja_cerrada_por_dia_hasta_hoy(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        $desde = CarbonImmutable::parse('2026-11-01 00:00:00');
        $hasta = CarbonImmutable::parse('2026-11-20 23:59:59');
        $dias = (int) $desde->diffInDays($hasta->startOfDay()) + 1;

        $this->assertSame(500, Venta::count());
        $this->assertSame($dias, Caja::count());
        $this->assertSame(0, Caja::query()->where('estado', EstadoCaja::Abierta->value)->count());
        $this->assertSame(0, Venta::query()->whereNotBetween('created_at', [$desde, $hasta])->count());
        $this->assertSame(0, Caja::query()->whereNotBetween('fecha_apertura', [$desde, $hasta])->count());

        $ventas = Venta::query()->with('caja')->orderBy('fecha_ticket')->get();
        $porDia = $ventas->groupBy(fn (Venta $venta): string => $venta->fecha_ticket->toDateString());

        $this->assertSame($dias, $porDia->count());

        foreach ($porDia as $ventasDelDia) {
            $this->assertSame(25, $ventasDelDia->count());
            $this->assertSame(
                range(1, $ventasDelDia->count()),
                $ventasDelDia->pluck('numero_ticket')->sort()->values()->all()
            );
        }

        foreach ($ventas as $venta) {
            $this->assertSame($venta->negocio_id, $venta->caja->negocio_id);
            $this->assertSame($venta->fecha_ticket->toDateString(), $venta->caja->fecha_apertura->toDateString());
        }
    }

    public function test_las_cajas_generadas_tienen_totales_y_cierre_coherentes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        foreach (Caja::query()->get() as $caja) {
            $resumen = $caja->resumenVentas();

            $this->assertSame($resumen['cantidad'], $caja->cantidad_ventas);
            $this->assertSame(round($resumen['total'], 2), round((float) $caja->total_ventas, 2));
            $this->assertSame(round($resumen['efectivo'], 2), round((float) $caja->total_efectivo, 2));
            $this->assertSame(round($resumen['qr'], 2), round((float) $caja->total_qr, 2));
            $this->assertSame(0.0, round((float) $caja->diferencia, 2));
            $this->assertSame(EstadoCaja::Cerrada, $caja->estado);
            $this->assertSame($caja->fecha_apertura->toDateString(), $caja->fecha_cierre->toDateString());
        }
    }

    public function test_es_idempotente(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ReportePruebasSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        $this->assertSame(500, Venta::count());
        $this->assertSame(20, Caja::count());
        $this->assertSame(1, User::query()->where('email', 'cajero1@jibas.test')->count());
        $this->assertSame(4, User::count());
        $this->assertSame(4, Producto::query()->whereHas(
            'categoria',
            fn ($query) => $query->where('nombre', 'PRODUCTOS REPORTE')
        )->count());
    }

    public function test_el_reporte_muestra_las_500_ventas_generadas(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        $cajero = User::query()->where('email', 'cajero1@jibas.test')->firstOrFail();

        $response = $this->actingAs($cajero)
            ->get(route('reportes.index', ['desde' => '2026-11-01', 'hasta' => '2026-11-20']))
            ->assertOk();

        $this->assertSame(500, $response->viewData('cantidadVentas'));
        $this->assertSame(500, $response->viewData('ventas')->total());
    }

    public function test_reutiliza_el_catalogo_existente_en_las_ventas(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ProduccionSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        $this->assertSame(14, Producto::count());
        $this->assertSame(0, Categoria::query()->where('nombre', 'PRODUCTOS REPORTE')->count());
        $this->assertSame(14, VentaDetalle::query()->distinct()->pluck('producto_id')->count());
    }

    public function test_las_cajas_tienen_datos_aleatorios_y_cierran_con_el_total_de_sus_ventas(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        $cajas = Caja::query()->orderBy('fecha_apertura')->get();
        $horasApertura = $cajas->pluck('fecha_apertura')->map(
            fn ($fecha): string => $fecha->format('H:i')
        );
        $horasCierre = $cajas->pluck('fecha_cierre')->map(
            fn ($fecha): string => $fecha->format('H:i')
        );

        $this->assertSame(20, $cajas->count());
        $this->assertSame(
            20,
            $cajas->pluck('fecha_apertura')->map(fn ($fecha): string => $fecha->toDateString())->unique()->count()
        );
        $this->assertGreaterThan(1, $cajas->pluck('monto_inicial')->unique()->count());
        $this->assertGreaterThan(1, $cajas->pluck('total_ventas')->unique()->count());
        $this->assertGreaterThan(1, $horasApertura->unique()->count());
        $this->assertGreaterThan(1, $horasCierre->unique()->count());
        $this->assertSame(2, Venta::query()->distinct()->pluck('metodo_pago')->count());
        $this->assertGreaterThan(1, Venta::query()->distinct()->pluck('total')->count());

        foreach ($cajas as $caja) {
            $resumen = $caja->resumenVentas();

            $this->assertSame(round($resumen['total'], 2), round((float) $caja->total_ventas, 2));
            $this->assertSame(round((float) $caja->monto_cierre, 2), round($caja->montoEsperadoEfectivo(), 2));
            $this->assertSame(0.0, round((float) $caja->diferencia, 2));
            $this->assertTrue($caja->fecha_cierre->greaterThan($caja->fecha_apertura));
            $this->assertSame($caja->fecha_apertura->toDateString(), $caja->fecha_cierre->toDateString());
            $this->assertStringStartsWith('SEEDER_REPORTE_MES', $caja->observaciones_apertura);
            $this->assertStringStartsWith('SEEDER_REPORTE_MES', $caja->observaciones_cierre);
        }
    }
}
