<?php

namespace Database\Seeders;

use App\Enums\EstadoCaja;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportePruebasSeeder extends Seeder
{
    private const CANTIDAD_VENTAS = 500;

    private const MARCADOR = 'SEEDER_REPORTE_MES';

    /**
     * Seed 500 sales in the current month, with one closed cash register per day.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $ahora = CarbonImmutable::now();
            $desde = $ahora->startOfMonth();
            $hasta = $ahora->endOfMonth();

            $negocio = $this->negocio();
            $cajero = $this->cajero($negocio);
            $productos = $this->productos($negocio);

            $this->purgarGenerados($negocio, $desde, $hasta);
            $this->generarVentas($negocio, $cajero, $productos, $desde, $hasta);
        });
    }

    private function negocio(): Negocio
    {
        return Negocio::firstOrCreate(
            ['nombre_comercial' => 'Mi Negocio'],
            ['eslogan' => null, 'logo' => null]
        );
    }

    private function cajero(Negocio $negocio): User
    {
        return User::query()
            ->where('negocio_id', $negocio->id)
            ->where('username', 'cajero1')
            ->first()
            ?? User::query()
                ->where('negocio_id', $negocio->id)
                ->where('rol', Rol::Cajero->value)
                ->firstOrFail();
    }

    /**
     * @return array<int, Producto>
     */
    private function productos(Negocio $negocio): array
    {
        $categorias = Categoria::query()
            ->where('negocio_id', $negocio->id)
            ->pluck('id');

        $productos = Producto::query()
            ->whereIn('categoria_id', $categorias)
            ->get();

        if ($productos->isNotEmpty()) {
            return $productos->all();
        }

        $categoria = Categoria::firstOrCreate(
            ['negocio_id' => $negocio->id, 'nombre' => 'PRODUCTOS REPORTE'],
            ['activa' => true, 'orden' => 99]
        );

        return collect([
            ['Jiba', 12.00],
            ['Morcilla', 9.00],
            ['Refresco 2Lt', 15.00],
            ['Agua 500ml', 7.00],
        ])->map(fn (array $producto): Producto => Producto::firstOrCreate(
            ['categoria_id' => $categoria->id, 'nombre' => $producto[0]],
            ['precio' => $producto[1], 'stock' => null]
        ))->all();
    }

    private function purgarGenerados(Negocio $negocio, CarbonImmutable $desde, CarbonImmutable $hasta): void
    {
        $ventas = Venta::query()
            ->where('negocio_id', $negocio->id)
            ->whereBetween('created_at', [$desde, $hasta])
            ->where('glosa', self::MARCADOR)
            ->pluck('id');

        VentaDetalle::query()
            ->whereIn('venta_id', $ventas)
            ->delete();

        Venta::query()
            ->whereIn('id', $ventas)
            ->delete();

        Caja::query()
            ->where('negocio_id', $negocio->id)
            ->whereBetween('fecha_apertura', [$desde, $hasta])
            ->where('observaciones_apertura', self::MARCADOR)
            ->delete();
    }

    /**
     * @param  array<int, Producto>  $productos
     */
    private function generarVentas(
        Negocio $negocio,
        User $cajero,
        array $productos,
        CarbonImmutable $desde,
        CarbonImmutable $hasta
    ): void {
        $dias = $hasta->day;
        $porDia = intdiv(self::CANTIDAD_VENTAS, $dias);
        $sobrante = self::CANTIDAD_VENTAS % $dias;

        for ($dia = 1; $dia <= $dias; $dia++) {
            $fecha = $desde->addDays($dia - 1);
            $cantidad = $porDia + ($dia <= $sobrante ? 1 : 0);
            $caja = $this->crearCaja($negocio, $cajero, $fecha);

            for ($numeroTicket = 1; $numeroTicket <= $cantidad; $numeroTicket++) {
                $this->crearVenta($caja, $cajero, $productos, $fecha, $numeroTicket);
            }

            $this->cerrarCaja($caja, $cajero, $fecha);
        }
    }

    private function crearCaja(Negocio $negocio, User $cajero, CarbonImmutable $fecha): Caja
    {
        return Caja::create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajero->id,
            'cerrada_por_id' => $cajero->id,
            'estado' => EstadoCaja::Cerrada,
            'monto_inicial' => 100.00,
            'observaciones_apertura' => self::MARCADOR,
            'fecha_apertura' => $fecha->setTime(8, 0),
            'fecha_cierre' => $fecha->setTime(20, 0),
            'total_ventas' => 0,
            'total_efectivo' => 0,
            'total_qr' => 0,
            'cantidad_ventas' => 0,
            'monto_cierre' => 100.00,
            'monto_esperado_efectivo' => 100.00,
            'diferencia' => 0,
            'observaciones_cierre' => self::MARCADOR,
        ]);
    }

    /**
     * @param  array<int, Producto>  $productos
     */
    private function crearVenta(
        Caja $caja,
        User $cajero,
        array $productos,
        CarbonImmutable $fecha,
        int $numeroTicket
    ): void {
        $hora = $fecha->setTime(9, 0)->addMinutes(($numeroTicket - 1) * 5);
        $metodoPago = $numeroTicket % 4 === 0 ? MetodoPago::Qr : MetodoPago::Efectivo;
        $cantidadItems = 1 + ($numeroTicket % 3);

        $detalles = [];

        for ($indice = 0; $indice < $cantidadItems; $indice++) {
            $producto = $productos[($numeroTicket + $indice) % count($productos)];
            $precio = (float) $producto->precio;
            $cantidad = 1 + (($numeroTicket + $indice) % 2);

            $detalles[] = [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'precio_unitario' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => round($precio * $cantidad, 2),
                'es_promocion' => false,
            ];
        }

        $total = round(collect($detalles)->sum('subtotal'), 2);

        $venta = Venta::factory()->create([
            'negocio_id' => $caja->negocio_id,
            'caja_id' => $caja->id,
            'user_id' => $cajero->id,
            'numero_ticket' => $numeroTicket,
            'fecha_ticket' => $fecha->toDateString(),
            'total' => $total,
            'metodo_pago' => $metodoPago,
            'monto_recibido' => $metodoPago === MetodoPago::Efectivo ? round($total + 5, 2) : null,
            'cambio' => $metodoPago === MetodoPago::Efectivo ? 5.00 : null,
            'glosa' => self::MARCADOR,
            'cliente' => null,
            'para_llevar' => $numeroTicket % 2 === 0,
            'created_at' => $hora,
            'updated_at' => $hora,
        ]);

        foreach ($detalles as $detalle) {
            $venta->detalles()->create($detalle);
        }
    }

    private function cerrarCaja(Caja $caja, User $cajero, CarbonImmutable $fecha): void
    {
        $resumen = $caja->resumenVentas();
        $montoEsperado = round((float) $caja->monto_inicial + $resumen['efectivo'], 2);

        $caja->update([
            'estado' => EstadoCaja::Cerrada,
            'cerrada_por_id' => $cajero->id,
            'fecha_cierre' => $fecha->setTime(20, 0),
            'total_ventas' => $resumen['total'],
            'total_efectivo' => $resumen['efectivo'],
            'total_qr' => $resumen['qr'],
            'cantidad_ventas' => $resumen['cantidad'],
            'monto_esperado_efectivo' => $montoEsperado,
            'monto_cierre' => $montoEsperado,
            'diferencia' => 0,
        ]);
    }
}
