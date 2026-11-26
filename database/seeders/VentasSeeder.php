<?php

namespace Database\Seeders;

use App\Enums\EstadoCaja;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Caja;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Support\CatalogoCache;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VentasSeeder extends Seeder
{
    private const FECHA_INICIO = '2026-10-01';

    private const FECHA_FIN = '2026-10-31';

    private const VENTAS_POR_CAJA = 100;

    private const MAX_PRODUCTOS_POR_VENTA = 4;

    private const STOCK_INICIAL = 100;

    /**
     * Genera un escenario de ventas para octubre de 2026.
     *
     * El seeder es idempotente: reemplaza las cajas y ventas previas del
     * negocio en ese rango antes de volver a crearlas.
     */
    public function run(): void
    {
        $negocio = Negocio::query()
            ->where('nombre_comercial', DatabaseSeeder::NOMBRE_COMERCIAL)
            ->firstOrFail();

        $cajeros = User::query()
            ->where('negocio_id', $negocio->id)
            ->where('rol', Rol::Cajero->value)
            ->get();

        if ($cajeros->isEmpty()) {
            $cajeros = User::query()
                ->where('negocio_id', $negocio->id)
                ->get();
        }

        if ($cajeros->isEmpty()) {
            throw new RuntimeException('El negocio no tiene usuarios; ejecuta primero DatabaseSeeder.');
        }

        $productos = $this->productosActivos($negocio);

        if ($productos->isEmpty()) {
            throw new RuntimeException('El negocio no tiene productos activos; ejecuta primero RestaurantePollosSeeder.');
        }

        $inicio = Carbon::parse(self::FECHA_INICIO)->startOfDay();
        $fin = Carbon::parse(self::FECHA_FIN)->endOfDay();
        $decrementosStock = collect();

        DB::transaction(function () use ($negocio, $cajeros, $productos, $inicio, $fin, &$decrementosStock): void {
            $this->limpiarRango($negocio, $inicio, $fin);
            $this->reiniciarStock($productos);

            for ($fecha = $inicio->copy(); $fecha->lessThanOrEqualTo($fin); $fecha->addDay()) {
                $this->crearCajaConVentas($negocio, $cajeros, $productos, $fecha->copy(), $decrementosStock);
            }

            $this->aplicarDecrementosStock($decrementosStock);
        });

        CatalogoCache::invalidar($negocio->id);

        $this->command?->info('VentasSeeder: 31 cajas y 3.100 ventas generadas para octubre de 2026.');
    }

    /**
     * @return Collection<int, array{id: int, nombre: string, precio_centavos: int}>
     */
    private function productosActivos(Negocio $negocio): Collection
    {
        return Producto::query()
            ->join('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->where('categorias.negocio_id', $negocio->id)
            ->where('categorias.activa', true)
            ->where('productos.activa', true)
            ->select(['productos.id', 'productos.nombre', 'productos.precio'])
            ->get()
            ->map(fn (Producto $producto): array => [
                'id' => (int) $producto->id,
                'nombre' => (string) $producto->nombre,
                'precio_centavos' => (int) round((float) $producto->precio * 100),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array{id: int, nombre: string, precio_centavos: int}>  $productos
     */
    private function reiniciarStock(Collection $productos): void
    {
        DB::table('productos')
            ->whereIn('id', $productos->pluck('id')->all())
            ->update([
                'stock' => self::STOCK_INICIAL,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  Collection<int, int>  $decrementos
     */
    private function aplicarDecrementosStock(Collection $decrementos): void
    {
        if ($decrementos->isEmpty()) {
            return;
        }

        $casos = $decrementos
            ->map(fn (int $cantidad, int $id): string => "WHEN {$id} THEN stock - {$cantidad}")
            ->implode(' ');

        DB::table('productos')
            ->whereIn('id', $decrementos->keys()->all())
            ->update([
                'stock' => DB::raw("CASE id {$casos} ELSE stock END"),
                'updated_at' => now(),
            ]);
    }

    private function limpiarRango(Negocio $negocio, Carbon $inicio, Carbon $fin): void
    {
        $cajaIds = Caja::query()
            ->where('negocio_id', $negocio->id)
            ->whereBetween('fecha_apertura', [$inicio, $fin])
            ->pluck('id');

        Venta::query()
            ->where('negocio_id', $negocio->id)
            ->where(function ($query) use ($cajaIds, $inicio, $fin): void {
                $query->whereBetween('fecha_ticket', [$inicio->toDateString(), $fin->toDateString()])
                    ->orWhereIn('caja_id', $cajaIds);
            })
            ->delete();

        Caja::query()
            ->whereIn('id', $cajaIds)
            ->delete();
    }

    /**
     * @param  Collection<int, User>  $cajeros
     * @param  Collection<int, array{id: int, nombre: string, precio_centavos: int}>  $productos
     * @param  Collection<int, int>  $decrementosStock
     */
    private function crearCajaConVentas(
        Negocio $negocio,
        Collection $cajeros,
        Collection $productos,
        Carbon $fecha,
        Collection $decrementosStock,
    ): void {
        $apertura = $fecha->copy()->setTime(8, 0);
        $cierre = $fecha->copy()->setTime(20, random_int(0, 30));
        $montoInicialCentavos = random_int(10000, 20000);

        $cajaId = DB::table('cajas')->insertGetId([
            'negocio_id' => $negocio->id,
            'user_id' => $cajeros->random()->id,
            'cerrada_por_id' => null,
            'estado' => EstadoCaja::Abierta->value,
            'monto_inicial' => $this->centavos($montoInicialCentavos),
            'observaciones_apertura' => null,
            'fecha_apertura' => $apertura,
            'total_ventas' => 0,
            'total_efectivo' => 0,
            'total_qr' => 0,
            'cantidad_ventas' => 0,
            'created_at' => $apertura,
            'updated_at' => $apertura,
        ]);

        $detalles = [];
        $totalCentavos = 0;
        $totalEfectivoCentavos = 0;
        $totalQrCentavos = 0;
        $fechaTicket = $fecha->toDateString();

        for ($ticket = 1; $ticket <= self::VENTAS_POR_CAJA; $ticket++) {
            $momento = $fecha->copy()->setTime(
                intdiv(random_int(481, 1199), 60),
                random_int(481, 1199) % 60,
            );

            $lineas = $productos
                ->shuffle()
                ->take(random_int(1, min(self::MAX_PRODUCTOS_POR_VENTA, $productos->count())));

            $totalVentaCentavos = 0;
            $detallesDeLaVenta = [];

            foreach ($lineas as $producto) {
                $cantidad = random_int(1, 5);
                $subtotalCentavos = $producto['precio_centavos'] * $cantidad;
                $totalVentaCentavos += $subtotalCentavos;
                $decrementosStock->put(
                    $producto['id'],
                    $decrementosStock->get($producto['id'], 0) + $cantidad,
                );

                $detallesDeLaVenta[] = [
                    'producto_id' => $producto['id'],
                    'promocion_id' => null,
                    'es_promocion' => false,
                    'nombre' => $producto['nombre'],
                    'precio_unitario' => $this->centavos($producto['precio_centavos']),
                    'cantidad' => $cantidad,
                    'subtotal' => $this->centavos($subtotalCentavos),
                    'glosa' => null,
                    'created_at' => $momento,
                    'updated_at' => $momento,
                ];
            }

            $metodoPago = random_int(1, 100) <= 70 ? MetodoPago::Efectivo : MetodoPago::Qr;

            if ($metodoPago === MetodoPago::Efectivo) {
                $montoRecibidoCentavos = (int) (ceil($totalVentaCentavos / 500) * 500);
                $cambioCentavos = $montoRecibidoCentavos - $totalVentaCentavos;
                $totalEfectivoCentavos += $totalVentaCentavos;
            } else {
                $montoRecibidoCentavos = null;
                $cambioCentavos = null;
                $totalQrCentavos += $totalVentaCentavos;
            }

            $ventaId = DB::table('ventas')->insertGetId([
                'negocio_id' => $negocio->id,
                'caja_id' => $cajaId,
                'user_id' => $cajeros->random()->id,
                'numero_ticket' => $ticket,
                'fecha_ticket' => $fechaTicket,
                'total' => $this->centavos($totalVentaCentavos),
                'metodo_pago' => $metodoPago->value,
                'monto_recibido' => $montoRecibidoCentavos === null
                    ? null
                    : $this->centavos($montoRecibidoCentavos),
                'cambio' => $cambioCentavos === null ? null : $this->centavos($cambioCentavos),
                'glosa' => null,
                'cliente' => null,
                'para_llevar' => (bool) random_int(0, 1),
                'created_at' => $momento,
                'updated_at' => $momento,
            ]);

            foreach ($detallesDeLaVenta as $detalle) {
                $detalles[] = ['venta_id' => $ventaId] + $detalle;
            }

            $totalCentavos += $totalVentaCentavos;
        }

        DB::table('venta_detalles')->insert($detalles);

        $montoEsperadoCentavos = $montoInicialCentavos + $totalEfectivoCentavos;
        $montoCierreCentavos = max(0, $montoEsperadoCentavos + random_int(-500, 500));

        DB::table('cajas')
            ->where('id', $cajaId)
            ->update([
                'estado' => EstadoCaja::Cerrada->value,
                'cerrada_por_id' => $cajeros->random()->id,
                'total_ventas' => $this->centavos($totalCentavos),
                'total_efectivo' => $this->centavos($totalEfectivoCentavos),
                'total_qr' => $this->centavos($totalQrCentavos),
                'cantidad_ventas' => self::VENTAS_POR_CAJA,
                'monto_cierre' => $this->centavos($montoCierreCentavos),
                'monto_esperado_efectivo' => $this->centavos($montoEsperadoCentavos),
                'diferencia' => $this->centavos($montoCierreCentavos - $montoEsperadoCentavos),
                'observaciones_cierre' => 'Cierre generado por VentasSeeder.',
                'fecha_cierre' => $cierre,
                'updated_at' => $cierre,
            ]);
    }

    private function centavos(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}
