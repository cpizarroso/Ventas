<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaDetallePromocionItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    /**
     * Sales report for the authenticated user's business.
     */
    public function index(Request $request): View
    {
        return view('reportes.index', $this->reporte($request));
    }

    /**
     * Download the filtered sales report as a CSV file.
     */
    public function exportar(Request $request): StreamedResponse
    {
        $reporte = $this->reporte($request);
        $consulta = (clone $reporte['consulta'])
            ->with(['user', 'detalles.promocionItems']);
        $sufijo = $reporte['cajeroSeleccionado'] !== null
            ? '-cajero-'.$reporte['cajeroSeleccionado']->id
            : '';
        $nombre = 'reporte-ventas-'.$reporte['desde']->format('Y-m-d').'_'.$reporte['hasta']->format('Y-m-d').$sufijo.'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $salida = fopen('php://output', 'w');

            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Venta', 'Fecha', 'Hora', 'Vendedor', 'Tipo', 'Para llevar', 'Promociones', 'Ítems', 'Total', 'Método', 'Recibido'], ';');

            $consulta->chunkById(500, function (Collection $ventas) use ($salida): void {
                foreach ($ventas as $venta) {
                    fputcsv($salida, [
                        $venta->numeroTicket(),
                        $venta->created_at->format('d/m/Y'),
                        $venta->created_at->format('H:i'),
                        $venta->user?->name ?? '—',
                        $venta->etiquetaTipoReporte(),
                        $venta->para_llevar ? 'Sí' : 'No',
                        $venta->promocionesNombres(),
                        $venta->detalles->sum('cantidad'),
                        number_format((float) $venta->total, 2, '.', ''),
                        $venta->metodo_pago?->etiqueta() ?? MetodoPago::Efectivo->etiqueta(),
                        $venta->monto_recibido !== null ? number_format((float) $venta->monto_recibido, 2, '.', '') : '',
                    ], ';');
                }
            }, 'id');

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Download the filtered sales report as a PDF file.
     */
    public function exportarPdf(Request $request): Response
    {
        $reporte = $this->reporte($request);
        $ventas = (clone $reporte['consulta'])
            ->with(['user', 'detalles.promocionItems'])
            ->ordenadasPorFechaYNumeroTicket()
            ->get();
        $sufijo = $reporte['cajeroSeleccionado'] !== null
            ? '-cajero-'.$reporte['cajeroSeleccionado']->id
            : '';
        $nombre = 'reporte-ventas-'.$reporte['desde']->format('Y-m-d').'_'.$reporte['hasta']->format('Y-m-d').$sufijo.'.pdf';

        return $this->generarPdf('reportes.pdf', [
            'negocio' => $reporte['negocio'],
            'usuario' => Auth::user(),
            'ventas' => $ventas,
            'desde' => $reporte['desde'],
            'hasta' => $reporte['hasta'],
            'cajeroSeleccionado' => $reporte['cajeroSeleccionado'],
            'busqueda' => $reporte['busqueda'],
            'totalVendido' => $reporte['totalVendido'],
            'cantidadVentas' => $reporte['cantidadVentas'],
        ], $nombre);
    }

    /**
     * Build the report data for the selected dates, cashier and search term.
     *
     * @return array{
     *     negocio: Negocio|null,
     *     ventas: LengthAwarePaginator<int, Venta>,
     *     consulta: Builder<Venta>,
     *     resumenProductos: Collection<int, object>,
     *     resumenPromociones: Collection<int, object>,
     *     itemsPromocion: Collection<int, object>,
     *     cajeros: Collection<int, User>,
     *     cajeroSeleccionado: User|null,
     *     cajeroId: int|null,
     *     busqueda: string,
     *     totalVendido: float,
     *     totalEfectivo: float,
     *     totalQr: float,
     *     cantidadVentas: int,
     *     itemsVendidos: int,
     *     ticketPromedio: float,
     *     puedeFiltrarPorCajero: bool,
     *     desde: CarbonImmutable,
     *     hasta: CarbonImmutable
     * }
     */
    private function reporte(Request $request): array
    {
        $user = Auth::user();
        $negocio = $user?->negocio;
        $esCajero = $user?->esCajero() ?? false;

        $reglas = [
            'desde' => ['nullable', 'date_format:Y-m-d', 'required_with:hasta'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:desde', 'after_or_equal:desde'],
            'busqueda' => ['nullable', 'string', 'max:100'],
        ];

        if (! $esCajero) {
            $reglas['cajero_id'] = [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('negocio_id', $negocio?->id)
                    ->where('rol', Rol::Cajero->value),
            ];
        }

        $datos = $request->validate($reglas);

        $hoy = CarbonImmutable::now();
        $desde = isset($datos['desde'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $datos['desde'])->startOfDay()
            : $hoy->startOfMonth();
        $hasta = isset($datos['hasta'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $datos['hasta'])->endOfDay()
            : $hoy->endOfMonth();
        $busqueda = trim((string) ($datos['busqueda'] ?? ''));

        $cajeroId = $esCajero
            ? $user->id
            : (isset($datos['cajero_id']) ? (int) $datos['cajero_id'] : null);

        $cajeros = $negocio === null
            ? collect()
            : ($esCajero
                ? collect([$user])
                : $negocio->usuarios()
                    ->where('rol', Rol::Cajero)
                    ->orderBy('name')
                    ->get());

        $cajeroSeleccionado = $esCajero
            ? $user
            : ($cajeroId !== null ? $cajeros->firstWhere('id', $cajeroId) : null);

        $consulta = $negocio === null
            ? Venta::query()->whereRaw('1 = 0')
            : $negocio->ventas()
                ->whereBetween('ventas.created_at', [$desde, $hasta])
                ->when($cajeroId !== null, fn (Builder $query) => $query->where('ventas.user_id', $cajeroId))
                ->when($busqueda !== '', fn (Builder $query) => $this->aplicarBusqueda($query, $busqueda, $negocio->id));

        $ventas = (clone $consulta)
            ->with(['user', 'detalles.promocionItems'])
            ->ordenadasPorFechaYNumeroTicket()
            ->paginate(10)
            ->withQueryString();

        $totales = (clone $consulta)
            ->selectRaw(
                'COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total,
                COALESCE(SUM(CASE WHEN metodo_pago = ? THEN total ELSE 0 END), 0) as efectivo,
                COALESCE(SUM(CASE WHEN metodo_pago = ? THEN total ELSE 0 END), 0) as qr',
                [MetodoPago::Efectivo->value, MetodoPago::Qr->value]
            )
            ->first();

        $itemsVendidos = $negocio === null
            ? 0
            : (int) (clone $consulta)
                ->join('venta_detalles', 'venta_detalles.venta_id', '=', 'ventas.id')
                ->sum('venta_detalles.cantidad');

        $resumenProductos = $negocio === null
            ? collect()
            : VentaDetalle::query()
                ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                ->leftJoin('productos', 'productos.id', '=', 'venta_detalles.producto_id')
                ->where('ventas.negocio_id', $negocio->id)
                ->whereBetween('ventas.created_at', [$desde, $hasta])
                ->where('venta_detalles.es_promocion', false)
                ->when($cajeroId !== null, fn ($query) => $query->where('ventas.user_id', $cajeroId))
                ->when($busqueda !== '', fn ($query) => $this->aplicarBusqueda($query, $busqueda, $negocio->id))
                ->selectRaw('venta_detalles.producto_id as producto_id, MAX(productos.id) as producto_actual_id, MAX(productos.stock) as stock, venta_detalles.nombre as nombre, SUM(venta_detalles.cantidad) as cantidad, SUM(venta_detalles.subtotal) as total')
                ->groupBy('venta_detalles.producto_id', 'venta_detalles.nombre')
                ->orderByDesc('total')
                ->get();

        $resumenPromociones = $negocio === null
            ? collect()
            : VentaDetalle::query()
                ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                ->where('ventas.negocio_id', $negocio->id)
                ->whereBetween('ventas.created_at', [$desde, $hasta])
                ->where('venta_detalles.es_promocion', true)
                ->when($cajeroId !== null, fn ($query) => $query->where('ventas.user_id', $cajeroId))
                ->when($busqueda !== '', fn ($query) => $this->aplicarBusqueda($query, $busqueda, $negocio->id))
                ->selectRaw('venta_detalles.nombre as nombre, SUM(venta_detalles.cantidad) as cantidad, SUM(venta_detalles.subtotal) as total')
                ->groupBy('venta_detalles.nombre')
                ->orderByDesc('total')
                ->get();

        $itemsPromocion = $negocio === null
            ? collect()
            : VentaDetallePromocionItem::query()
                ->join('venta_detalles', 'venta_detalles.id', '=', 'venta_detalle_promocion_items.venta_detalle_id')
                ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                ->where('ventas.negocio_id', $negocio->id)
                ->whereBetween('ventas.created_at', [$desde, $hasta])
                ->when($cajeroId !== null, fn ($query) => $query->where('ventas.user_id', $cajeroId))
                ->when($busqueda !== '', fn ($query) => $this->aplicarBusqueda($query, $busqueda, $negocio->id))
                ->selectRaw('venta_detalle_promocion_items.nombre as nombre, SUM(venta_detalle_promocion_items.cantidad) as cantidad')
                ->groupBy('venta_detalle_promocion_items.nombre')
                ->orderByDesc('cantidad')
                ->get();

        $totalVendido = (float) ($totales->total ?? 0);
        $cantidadVentas = (int) ($totales->cantidad ?? 0);

        return [
            'negocio' => $negocio,
            'ventas' => $ventas,
            'consulta' => $consulta,
            'resumenProductos' => $resumenProductos,
            'resumenPromociones' => $resumenPromociones,
            'itemsPromocion' => $itemsPromocion,
            'cajeros' => $cajeros,
            'cajeroSeleccionado' => $cajeroSeleccionado,
            'cajeroId' => $cajeroId,
            'busqueda' => $busqueda,
            'totalVendido' => $totalVendido,
            'totalEfectivo' => (float) ($totales->efectivo ?? 0),
            'totalQr' => (float) ($totales->qr ?? 0),
            'cantidadVentas' => $cantidadVentas,
            'itemsVendidos' => $itemsVendidos,
            'ticketPromedio' => $cantidadVentas === 0 ? 0.0 : $totalVendido / $cantidadVentas,
            'puedeFiltrarPorCajero' => ! $esCajero,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * Apply the free-text search to a query that joins the sales table.
     */
    private function aplicarBusqueda(Builder|QueryBuilder $query, string $busqueda, int $negocioId): void
    {
        $like = '%'.$busqueda.'%';

        $query->where(function (Builder|QueryBuilder $subquery) use ($busqueda, $like, $negocioId): void {
            $subquery->where('ventas.cliente', 'like', $like)
                ->orWhere('ventas.glosa', 'like', $like)
                ->orWhere('ventas.numero_ticket', 'like', $like)
                ->orWhereIn(
                    'ventas.user_id',
                    User::query()
                        ->select('id')
                        ->where('negocio_id', $negocioId)
                        ->where('name', 'like', $like)
                );

            if (ctype_digit($busqueda)) {
                $subquery->orWhere('ventas.id', (int) $busqueda);
            }
        });
    }
}
