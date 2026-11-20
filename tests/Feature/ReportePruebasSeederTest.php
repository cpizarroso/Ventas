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

    public function test_genera_500_ventas_y_una_caja_cerrada_por_dia_del_mes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 12:00:00'));
        $this->seed(SuperadminSeeder::class);
        $this->seed(ReportePruebasSeeder::class);

        $dias = CarbonImmutable::parse('2026-11-30')->day;
        $desde = CarbonImmutable::parse('2026-11-01 00:00:00');
        $hasta = CarbonImmutable::parse('2026-11-30 23:59:59');

        $this->assertSame(500, Venta::count());
        $this->assertSame($dias, Caja::count());
        $this->assertSame(0, Caja::query()->where('estado', EstadoCaja::Abierta->value)->count());
        $this->assertSame(0, Venta::query()->whereNotBetween('created_at', [$desde, $hasta])->count());

        $ventas = Venta::query()->with('caja')->orderBy('fecha_ticket')->get();
        $porDia = $ventas->groupBy(fn (Venta $venta): string => $venta->fecha_ticket->toDateString());

        $this->assertSame($dias, $porDia->count());

        foreach ($porDia as $ventasDelDia) {
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
        $this->assertSame(CarbonImmutable::parse('2026-11-30')->day, Caja::count());
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
            ->get(route('reportes.index', ['desde' => '2026-11-01', 'hasta' => '2026-11-30']))
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
}
