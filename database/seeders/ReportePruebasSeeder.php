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
use Random\Engine\Mt19937;
use Random\Randomizer;

class ReportePruebasSeeder extends Seeder
{
    private const CANTIDAD_VENTAS = 500;

    private const FECHA_DESDE = '2026-11-01';

    private const MARCADOR = 'SEEDER_REPORTE_MES';

    private const SEMILLA = 20261101;

    /** @var array<int, string> */
    private const OBSERVACIONES_APERTURA = [
        'Turno de la mañana con sencillo completo',
        'Apertura con cambio suficiente para clientes',
        'Turno normal, sin novedades al abrir',
        'Apertura anticipada antes de llenar el local',
        'Sencillo reducido, solicitar reposición',
    ];

    /** @var array<int, string> */
    private const OBSERVACIONES_CIERRE = [
        'Cierre cuadrado, sin diferencias',
        'Efectivo contado al final del turno',
        'Ventas conciliadas con los cobros QR',
        'Turno cerrado después del último cliente',
    ];

    /**
     * Seed 500 sales between November 1 and today, with one opened and closed cash register per day.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $desde = CarbonImmutable::parse(self::FECHA_DESDE)->startOfDay();
            $hasta = CarbonImmutable::today()->endOfDay();

            $negocio = $this->negocio();
            $cajero = $this->cajero($negocio);
            $productos = $this->productos($negocio);
            $aleatorio = new Randomizer(new Mt19937(self::SEMILLA));

            $this->purgarGenerados($negocio);
            $this->generarVentas($negocio, $cajero, $productos, $desde, $hasta, $aleatorio);
        });
    }

    private function negocio(): Negocio
    {
        return Negocio::firstOrCreate(
            ['nombre_comercial' => DatabaseSeeder::NOMBRE_COMERCIAL],
            ['eslogan' => DatabaseSeeder::ESLOGAN, 'logo' => null]
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

    private function purgarGenerados(Negocio $negocio): void
    {
        $ventas = Venta::query()
            ->where('negocio_id', $negocio->id)
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
            ->where('observaciones_apertura', 'like', self::MARCADOR.'%')
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
        CarbonImmutable $hasta,
        Randomizer $aleatorio
    ): void {
        $dias = (int) $desde->diffInDays($hasta->startOfDay()) + 1;
        $porDia = intdiv(self::CANTIDAD_VENTAS, $dias);
        $sobrante = self::CANTIDAD_VENTAS % $dias;

        for ($dia = 1; $dia <= $dias; $dia++) {
            $fecha = $desde->addDays($dia - 1);
            $cantidad = $porDia + ($dia <= $sobrante ? 1 : 0);
            $montoInicial = round($aleatorio->getInt(5000, 20000) / 100, 2);
            $fechaApertura = $fecha->setTime(7, 30)->addMinutes($aleatorio->getInt(0, 20) * 5);
            $fechaCierre = $fecha->setTime(19, 0)->addMinutes($aleatorio->getInt(0, 18) * 5);
            $observacionApertura = self::OBSERVACIONES_APERTURA[
                $aleatorio->getInt(0, count(self::OBSERVACIONES_APERTURA) - 1)
            ];
            $observacionCierre = self::OBSERVACIONES_CIERRE[
                $aleatorio->getInt(0, count(self::OBSERVACIONES_CIERRE) - 1)
            ];

            $caja = $this->crearCaja(
                $negocio,
                $cajero,
                $montoInicial,
                $fechaApertura,
                self::MARCADOR.' · '.$observacionApertura
            );

            for ($numeroTicket = 1; $numeroTicket <= $cantidad; $numeroTicket++) {
                $this->crearVenta($caja, $cajero, $productos, $fecha, $fechaApertura, $numeroTicket, $aleatorio);
            }

            $this->cerrarCaja(
                $caja,
                $cajero,
                $fechaCierre,
                self::MARCADOR.' · '.$observacionCierre
            );
        }
    }

    private function crearCaja(
        Negocio $negocio,
        User $cajero,
        float $montoInicial,
        CarbonImmutable $fechaApertura,
        string $observacionesApertura
    ): Caja {
        return Caja::create([
            'negocio_id' => $negocio->id,
            'user_id' => $cajero->id,
            'estado' => EstadoCaja::Abierta,
            'monto_inicial' => $montoInicial,
            'observaciones_apertura' => $observacionesApertura,
            'fecha_apertura' => $fechaApertura,
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
        CarbonImmutable $fechaApertura,
        int $numeroTicket,
        Randomizer $aleatorio
    ): void {
        $hora = $fechaApertura->addMinutes(10 + ($numeroTicket - 1) * $aleatorio->getInt(5, 10));
        $metodoPago = $aleatorio->getInt(0, 4) === 0 ? MetodoPago::Qr : MetodoPago::Efectivo;
        $cantidadItems = $aleatorio->getInt(1, 3);

        $detalles = [];

        for ($indice = 0; $indice < $cantidadItems; $indice++) {
            $producto = $productos[$aleatorio->getInt(0, count($productos) - 1)];
            $precio = (float) $producto->precio;
            $cantidad = $aleatorio->getInt(1, 3);

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
        $montoRecibido = $metodoPago === MetodoPago::Efectivo
            ? round($total + $aleatorio->getInt(1, 10), 2)
            : null;

        $venta = Venta::factory()->create([
            'negocio_id' => $caja->negocio_id,
            'caja_id' => $caja->id,
            'user_id' => $cajero->id,
            'numero_ticket' => $numeroTicket,
            'fecha_ticket' => $fecha->toDateString(),
            'total' => $total,
            'metodo_pago' => $metodoPago,
            'monto_recibido' => $montoRecibido,
            'cambio' => $montoRecibido !== null ? round($montoRecibido - $total, 2) : null,
            'glosa' => self::MARCADOR,
            'cliente' => null,
            'para_llevar' => $aleatorio->getInt(0, 1) === 1,
            'created_at' => $hora,
            'updated_at' => $hora,
        ]);

        foreach ($detalles as $detalle) {
            $venta->detalles()->create($detalle);
        }
    }

    private function cerrarCaja(
        Caja $caja,
        User $cajero,
        CarbonImmutable $fechaCierre,
        string $observacionesCierre
    ): void {
        $resumen = $caja->resumenVentas();
        $montoEsperado = round((float) $caja->monto_inicial + $resumen['efectivo'], 2);

        $caja->update([
            'estado' => EstadoCaja::Cerrada,
            'cerrada_por_id' => $cajero->id,
            'fecha_cierre' => $fechaCierre,
            'total_ventas' => $resumen['total'],
            'total_efectivo' => $resumen['efectivo'],
            'total_qr' => $resumen['qr'],
            'cantidad_ventas' => $resumen['cantidad'],
            'monto_esperado_efectivo' => $montoEsperado,
            'monto_cierre' => $montoEsperado,
            'diferencia' => 0,
            'observaciones_cierre' => $observacionesCierre,
        ]);
    }
}
