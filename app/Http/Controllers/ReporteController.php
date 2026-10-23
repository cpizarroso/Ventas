<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        $ventas = $reporte['ventas'];
        $sufijo = $reporte['cajeroSeleccionado'] !== null
            ? '-cajero-'.$reporte['cajeroSeleccionado']->id
            : '';
        $nombre = 'reporte-ventas-'.$reporte['desde']->format('Y-m-d').'_'.$reporte['hasta']->format('Y-m-d').$sufijo.'.csv';

        return response()->streamDownload(function () use ($ventas): void {
            $salida = fopen('php://output', 'w');

            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Venta', 'Fecha', 'Hora', 'Vendedor', 'Ítems', 'Total', 'Método', 'Recibido'], ';');

            foreach ($ventas as $venta) {
                fputcsv($salida, [
                    $venta->id,
                    $venta->created_at->format('d/m/Y'),
                    $venta->created_at->format('H:i'),
                    $venta->user?->name ?? '—',
                    $venta->detalles->sum('cantidad'),
                    number_format((float) $venta->total, 2, '.', ''),
                    $venta->metodo_pago?->etiqueta() ?? MetodoPago::Efectivo->etiqueta(),
                    $venta->monto_recibido !== null ? number_format((float) $venta->monto_recibido, 2, '.', '') : '',
                ], ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the report data for the selected dates and optional cashier.
     *
     * @return array{
     *     negocio: Negocio|null,
     *     ventas: Collection<int, Venta>,
     *     resumenProductos: Collection<int, object>,
     *     cajeros: Collection<int, User>,
     *     cajeroSeleccionado: User|null,
     *     cajeroId: int|null,
     *     totalVendido: float,
     *     totalEfectivo: float,
     *     totalQr: float,
     *     cantidadVentas: int,
     *     itemsVendidos: int,
     *     ticketPromedio: float,
     *     desde: CarbonImmutable,
     *     hasta: CarbonImmutable
     * }
     */
    private function reporte(Request $request): array
    {
        $negocio = Auth::user()?->negocio;

        $datos = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d', 'required_with:hasta'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:desde', 'after_or_equal:desde'],
            'cajero_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('negocio_id', $negocio?->id)
                    ->where('rol', Rol::Cajero->value),
            ],
        ]);

        $hoy = CarbonImmutable::now();
        $desde = isset($datos['desde'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $datos['desde'])->startOfDay()
            : $hoy->startOfMonth();
        $hasta = isset($datos['hasta'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $datos['hasta'])->endOfDay()
            : $hoy->endOfMonth();

        $cajeroId = isset($datos['cajero_id']) ? (int) $datos['cajero_id'] : null;

        $cajeros = $negocio === null
            ? collect()
            : $negocio->usuarios()
                ->where('rol', Rol::Cajero)
                ->orderBy('name')
                ->get();

        $cajeroSeleccionado = $cajeroId !== null
            ? $cajeros->firstWhere('id', $cajeroId)
            : null;

        $ventas = $negocio === null
            ? collect()
            : $negocio->ventas()
                ->with(['user', 'detalles'])
                ->whereBetween('created_at', [$desde, $hasta])
                ->when($cajeroId !== null, fn (Builder $query) => $query->where('user_id', $cajeroId))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

        $resumenProductos = $negocio === null
            ? collect()
            : VentaDetalle::query()
                ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
                ->where('ventas.negocio_id', $negocio->id)
                ->whereBetween('ventas.created_at', [$desde, $hasta])
                ->when($cajeroId !== null, fn ($query) => $query->where('ventas.user_id', $cajeroId))
                ->selectRaw('venta_detalles.nombre as nombre, SUM(venta_detalles.cantidad) as cantidad, SUM(venta_detalles.subtotal) as total')
                ->groupBy('venta_detalles.nombre')
                ->orderByDesc('total')
                ->get();

        $totalVendido = (float) $ventas->sum(fn (Venta $venta) => (float) $venta->total);

        $ventasEfectivo = $ventas->filter(fn (Venta $venta) => $venta->metodo_pago === MetodoPago::Efectivo);
        $ventasQr = $ventas->filter(fn (Venta $venta) => $venta->metodo_pago === MetodoPago::Qr);

        return [
            'negocio' => $negocio,
            'ventas' => $ventas,
            'resumenProductos' => $resumenProductos,
            'cajeros' => $cajeros,
            'cajeroSeleccionado' => $cajeroSeleccionado,
            'cajeroId' => $cajeroId,
            'totalVendido' => $totalVendido,
            'totalEfectivo' => (float) $ventasEfectivo->sum(fn (Venta $venta) => (float) $venta->total),
            'totalQr' => (float) $ventasQr->sum(fn (Venta $venta) => (float) $venta->total),
            'cantidadVentas' => $ventas->count(),
            'itemsVendidos' => (int) $ventas->sum(fn (Venta $venta) => $venta->detalles->sum('cantidad')),
            'ticketPromedio' => $ventas->isEmpty() ? 0.0 : $totalVendido / $ventas->count(),
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }
}
