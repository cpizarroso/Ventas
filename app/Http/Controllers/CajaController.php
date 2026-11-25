<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCaja;
use App\Models\Caja;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaDetallePromocionItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CajaController extends Controller
{
    /**
     * List and summarize cash registers for the authenticated business.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Caja::class);

        $filtros = $this->filtros($request);
        $negocio = $filtros['negocio'];
        $usuario = Auth::user();
        $esCajero = $usuario?->esCajero() ?? false;

        $resumen = (clone $filtros['consulta'])
            ->selectRaw('COUNT(*) as cantidad, COALESCE(SUM(monto_inicial), 0) as monto_inicial, COALESCE(SUM(total_ventas), 0) as total_ventas, COALESCE(SUM(total_efectivo), 0) as total_efectivo, COALESCE(SUM(total_qr), 0) as total_qr, COALESCE(SUM(diferencia), 0) as diferencia')
            ->first();

        $graficos = $this->datosGraficos($filtros);

        $cajas = $filtros['consulta']
            ->with(['user', 'cerradaPor'])
            ->orderByDesc('fecha_apertura')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('cajas.index', [
            'negocio' => $negocio,
            'cajas' => $cajas,
            'cajaAbiertaActual' => $usuario !== null ? $this->cajaAbiertaDelUsuario($usuario) : null,
            'usuarios' => $this->usuariosDisponibles($negocio, $usuario, $esCajero),
            'esCajero' => $esCajero,
            'estado' => $filtros['estado'],
            'usuarioId' => $filtros['usuarioId'],
            'desde' => $filtros['desde'],
            'hasta' => $filtros['hasta'],
            'graficoProductos' => $graficos['productos'],
            'graficoVentas' => $graficos['ventas'],
            'granularidadVentas' => $graficos['granularidad'],
            'resumen' => [
                'cantidad' => (int) ($resumen->cantidad ?? 0),
                'monto_inicial' => (float) ($resumen->monto_inicial ?? 0),
                'total_ventas' => (float) ($resumen->total_ventas ?? 0),
                'total_efectivo' => (float) ($resumen->total_efectivo ?? 0),
                'total_qr' => (float) ($resumen->total_qr ?? 0),
                'diferencia' => (float) ($resumen->diferencia ?? 0),
            ],
        ]);
    }

    /**
     * Show the form to open a cash register.
     */
    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Caja::class);

        $user = Auth::user();

        abort_if($user === null || $user->negocio_id === null, 404);

        $origen = $request->query('origen') === 'ventas' ? 'ventas' : null;
        $cajaAbierta = $this->cajaAbiertaDelUsuario($user);

        if ($cajaAbierta !== null) {
            $redirect = $origen === 'ventas'
                ? redirect()->route('ventas.index')
                : redirect()->route('cajas.show', $cajaAbierta);

            return $redirect->with('status', 'Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.');
        }

        return view('cajas.create', [
            'cajaAbierta' => null,
            'origen' => $origen,
        ]);
    }

    /**
     * Store a newly opened cash register.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Caja::class);

        $user = Auth::user();

        abort_if($user === null || $user->negocio_id === null, 404);

        $datos = $request->validate([
            'monto_inicial' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'observaciones_apertura' => ['nullable', 'string', 'max:255'],
            'origen' => ['nullable', Rule::in(['ventas'])],
        ]);

        $volverAVentas = ($datos['origen'] ?? null) === 'ventas';

        if ($this->cajaAbiertaDelUsuario($user) !== null) {
            return $volverAVentas
                ? redirect()->route('ventas.index')->with('error', 'Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.')
                : redirect()->route('cajas.index')->with('error', 'Ya tienes una caja abierta. Debes cerrarla antes de abrir otra.');
        }

        $caja = Caja::query()->create([
            'negocio_id' => $user->negocio_id,
            'user_id' => $user->id,
            'estado' => EstadoCaja::Abierta,
            'monto_inicial' => round((float) $datos['monto_inicial'], 2),
            'observaciones_apertura' => $datos['observaciones_apertura'] ?? null,
            'fecha_apertura' => now(),
        ]);

        return $volverAVentas
            ? redirect()->route('ventas.index')
            : redirect()->route('cajas.show', $caja);
    }

    /**
     * Show the cash register and its sales.
     */
    public function show(Caja $caja): View
    {
        Gate::authorize('view', $caja);

        $caja->load(['negocio', 'user', 'cerradaPor']);

        $esAbierta = $caja->estaAbierta();
        $resumen = $esAbierta
            ? $caja->resumenVentas()
            : [
                'cantidad' => $caja->cantidad_ventas,
                'total' => (float) $caja->total_ventas,
                'efectivo' => (float) $caja->total_efectivo,
                'qr' => (float) $caja->total_qr,
            ];

        $ventas = $caja->ventas()
            ->with('user')
            ->ordenadasPorFechaYNumeroTicket()
            ->paginate(10, ['*'], 'ventas_page')
            ->withQueryString();

        return view('cajas.show', [
            'caja' => $caja,
            'ventas' => $ventas,
            'esAbierta' => $esAbierta,
            'resumen' => $resumen,
            'montoEsperado' => (float) $caja->monto_inicial + (float) ($esAbierta ? $resumen['efectivo'] : $caja->total_efectivo),
        ]);
    }

    /**
     * Show the closing summary before confirming it.
     */
    public function cerrar(Caja $caja): View|RedirectResponse
    {
        Gate::authorize('cerrar', $caja);

        $resumen = $caja->resumenVentas();

        return view('cajas.cerrar', [
            'caja' => $caja->load(['negocio', 'user']),
            'resumen' => $resumen,
            'montoEsperado' => (float) $caja->monto_inicial + $resumen['efectivo'],
        ]);
    }

    /**
     * Close the cash register and store its final totals.
     */
    public function cerrarStore(Request $request, Caja $caja): RedirectResponse
    {
        Gate::authorize('cerrar', $caja);

        $datos = $request->validate([
            'monto_cierre' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'observaciones_cierre' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $caja, $datos): void {
            $caja = Caja::query()
                ->whereKey($caja->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $caja->estaAbierta()) {
                return;
            }

            $resumen = $caja->resumenVentas();
            $montoEsperado = (float) $caja->monto_inicial + $resumen['efectivo'];
            $montoCierre = round((float) $datos['monto_cierre'], 2);

            $caja->update([
                'estado' => EstadoCaja::Cerrada,
                'cerrada_por_id' => $request->user()?->id,
                'fecha_cierre' => now(),
                'total_ventas' => $resumen['total'],
                'total_efectivo' => $resumen['efectivo'],
                'total_qr' => $resumen['qr'],
                'cantidad_ventas' => $resumen['cantidad'],
                'monto_esperado_efectivo' => $montoEsperado,
                'monto_cierre' => $montoCierre,
                'diferencia' => round($montoCierre - $montoEsperado, 2),
                'observaciones_cierre' => $datos['observaciones_cierre'] ?? null,
            ]);
        });

        $caja->refresh();

        if ($caja->estaAbierta()) {
            return redirect()
                ->route('cajas.show', $caja)
                ->with('error', 'La caja ya fue cerrada o no está abierta.');
        }

        return redirect()
            ->route('cajas.show', $caja)
            ->with('status', 'Caja cerrada correctamente.');
    }

    /**
     * Download the filtered cash register report as CSV.
     */
    public function exportar(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Caja::class);

        $filtros = $this->filtros($request);
        $consulta = $filtros['consulta'];

        $nombre = 'reporte-cajas-'.$filtros['desde']->format('Y-m-d').'_'.$filtros['hasta']->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $salida = fopen('php://output', 'w');

            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, [
                'Caja',
                'Estado',
                'Abierta por',
                'Cerrada por',
                'Apertura',
                'Cierre',
                'Monto inicial',
                'Ventas',
                'Total vendido',
                'Efectivo',
                'QR',
                'Efectivo esperado',
                'Efectivo contado',
                'Diferencia',
                'Observaciones apertura',
                'Observaciones cierre',
            ], ';');

            $this->cadaLoteDeCajas($consulta, function (Collection $cajas) use ($salida): void {
                foreach ($cajas as $caja) {
                    fputcsv($salida, [
                        $caja->id,
                        $caja->estado->etiqueta(),
                        $caja->user?->name ?? '—',
                        $caja->cerradaPor?->name ?? '',
                        $caja->fecha_apertura?->format('d/m/Y H:i') ?? '',
                        $caja->fecha_cierre?->format('d/m/Y H:i') ?? '',
                        number_format((float) $caja->monto_inicial, 2, '.', ''),
                        $caja->cantidad_ventas,
                        number_format((float) $caja->total_ventas, 2, '.', ''),
                        number_format((float) $caja->total_efectivo, 2, '.', ''),
                        number_format((float) $caja->total_qr, 2, '.', ''),
                        number_format($caja->montoEsperadoEfectivo(), 2, '.', ''),
                        $caja->monto_cierre !== null ? number_format((float) $caja->monto_cierre, 2, '.', '') : '',
                        $caja->diferencia !== null ? number_format((float) $caja->diferencia, 2, '.', '') : '',
                        $caja->observaciones_apertura ?? '',
                        $caja->observaciones_cierre ?? '',
                    ], ';');
                }
            });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Download the filtered cash register report as a PDF file.
     */
    public function exportarPdf(Request $request): Response
    {
        Gate::authorize('viewAny', Caja::class);

        $filtros = $this->filtros($request);
        $filasHtml = '';
        $cantidadCajas = 0;
        $totalVendido = 0.0;
        $diferencia = 0.0;

        $this->cadaLoteDeCajas($filtros['consulta'], function (Collection $cajas) use (&$filasHtml, &$cantidadCajas, &$totalVendido, &$diferencia): void {
            $filasHtml .= view('cajas._filas-pdf', ['cajas' => $cajas])->render();
            $cantidadCajas += $cajas->count();
            $totalVendido += (float) $cajas->sum('total_ventas');
            $diferencia += (float) $cajas->sum('diferencia');
        });

        $nombre = 'reporte-cajas-'.$filtros['desde']->format('Y-m-d').'_'.$filtros['hasta']->format('Y-m-d').'.pdf';

        return $this->generarPdf('cajas.pdf', [
            'negocio' => $filtros['negocio'],
            'usuario' => Auth::user(),
            'hayCajas' => $cantidadCajas > 0,
            'filasHtml' => $filasHtml,
            'cantidadCajas' => $cantidadCajas,
            'totalVendido' => $totalVendido,
            'diferencia' => $diferencia,
            'desde' => $filtros['desde'],
            'hasta' => $filtros['hasta'],
            'estado' => $filtros['estado'],
        ], $nombre);
    }

    /**
     * Iterate cash registers in fixed-size batches without losing the report
     * order. The cursor uses the same two columns as the ORDER BY, so batches
     * neither skip nor repeat a box when two share the same opening date.
     *
     * @param  Builder<Caja>  $consulta
     * @param  callable(Collection<int, Caja>): void  $callback
     */
    private function cadaLoteDeCajas(Builder $consulta, callable $callback, int $limite = 500): void
    {
        $consulta = (clone $consulta)
            ->select('cajas.*')
            ->with(['user', 'cerradaPor'])
            ->orderBy('fecha_apertura')
            ->orderBy('id');

        do {
            $cajas = (clone $consulta)->limit($limite)->get();

            if ($cajas->isEmpty()) {
                return;
            }

            $callback($cajas);

            $ultima = $cajas->last();
            $consulta->whereRaw('(fecha_apertura, id) > (?, ?)', [
                $ultima->fecha_apertura,
                (int) $ultima->id,
            ]);
        } while ($cajas->count() === $limite);
    }

    /**
     * Validate and build the shared cash register filters.
     *
     * @return array{
     *     negocio: Negocio|null,
     *     consulta: Builder<Caja>,
     *     estado: string|null,
     *     usuarioId: int|null,
     *     desde: CarbonImmutable,
     *     hasta: CarbonImmutable
     * }
     */
    private function filtros(Request $request): array
    {
        $user = Auth::user();
        $negocio = $user?->negocio;
        $esCajero = $user?->esCajero() ?? false;

        $reglas = [
            'desde' => ['nullable', 'date_format:Y-m-d', 'required_with:hasta'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'required_with:desde', 'after_or_equal:desde'],
            'estado' => ['nullable', Rule::enum(EstadoCaja::class)],
        ];

        if (! $esCajero) {
            $reglas['user_id'] = [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('negocio_id', $negocio?->id),
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

        $estado = $datos['estado'] ?? null;
        $usuarioId = $esCajero
            ? $user?->id
            : (isset($datos['user_id']) ? (int) $datos['user_id'] : null);

        $consulta = Caja::query()
            ->where('negocio_id', $negocio?->id)
            ->whereBetween('fecha_apertura', [$desde, $hasta])
            ->when($estado !== null, fn (Builder $query) => $query->where('estado', $estado))
            ->when($usuarioId !== null, fn (Builder $query) => $query->where('user_id', $usuarioId));

        return [
            'negocio' => $negocio,
            'consulta' => $consulta,
            'estado' => $estado,
            'usuarioId' => $usuarioId,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * Build the chart datasets with the same filters used by the cash register list.
     *
     * @param  array{negocio: Negocio|null, consulta: Builder<Caja>, estado: string|null, usuarioId: int|null, desde: CarbonImmutable, hasta: CarbonImmutable}  $filtros
     * @return array{productos: array<int, array{nombre: string, cantidad: int}>, ventas: array<int, array{periodo: string, etiqueta: string, total: float, cantidad: int}>, granularidad: string}
     */
    private function datosGraficos(array $filtros): array
    {
        $negocio = $filtros['negocio'];

        if ($negocio === null) {
            return ['productos' => [], 'ventas' => [], 'granularidad' => 'dia'];
        }

        $cajaIds = (clone $filtros['consulta'])
            ->pluck('cajas.id')
            ->unique()
            ->values()
            ->all();

        $productosNormales = VentaDetalle::query()
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->where('ventas.negocio_id', $negocio->id)
            ->whereIn('ventas.caja_id', $cajaIds)
            ->where('venta_detalles.es_promocion', false)
            ->selectRaw('venta_detalles.nombre as nombre, SUM(venta_detalles.cantidad) as cantidad')
            ->groupBy('venta_detalles.nombre')
            ->get();

        $productosPromocion = VentaDetallePromocionItem::query()
            ->join('venta_detalles', 'venta_detalles.id', '=', 'venta_detalle_promocion_items.venta_detalle_id')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->where('ventas.negocio_id', $negocio->id)
            ->whereIn('ventas.caja_id', $cajaIds)
            ->selectRaw('venta_detalle_promocion_items.nombre as nombre, SUM(venta_detalle_promocion_items.cantidad) as cantidad')
            ->groupBy('venta_detalle_promocion_items.nombre')
            ->get();

        $productos = $productosNormales
            ->concat($productosPromocion)
            ->groupBy('nombre')
            ->map(fn ($filas, string $nombre) => [
                'nombre' => $nombre,
                'cantidad' => (int) $filas->sum('cantidad'),
            ])
            ->sortByDesc('cantidad')
            ->values()
            ->all();

        $desde = $filtros['desde'];
        $hasta = $filtros['hasta'];
        $dias = $desde->diffInDays($hasta) + 1;

        $granularidad = match (true) {
            $dias <= 31 => 'dia',
            $dias <= 180 => 'semana',
            default => 'mes',
        };

        $formatoSql = match ($granularidad) {
            'semana' => '%x-%v',
            'mes' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $porPeriodo = Venta::query()
            ->where('ventas.negocio_id', $negocio->id)
            ->whereIn('ventas.caja_id', $cajaIds)
            ->selectRaw(
                'DATE_FORMAT(COALESCE(ventas.fecha_ticket, DATE(ventas.created_at)), ?) as periodo, COUNT(*) as cantidad, COALESCE(SUM(ventas.total), 0) as total',
                [$formatoSql]
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->keyBy('periodo');

        $claves = [];
        $fecha = $desde;

        while ($fecha->lessThanOrEqualTo($hasta)) {
            $claves[] = match ($granularidad) {
                'semana' => $fecha->format('o-W'),
                'mes' => $fecha->format('Y-m'),
                default => $fecha->format('Y-m-d'),
            };

            $fecha = match ($granularidad) {
                'semana' => $fecha->addWeek(),
                'mes' => $fecha->addMonth(),
                default => $fecha->addDay(),
            };
        }

        $ventas = collect(array_values(array_unique($claves)))
            ->map(function (string $clave) use ($porPeriodo, $granularidad) {
                $fila = $porPeriodo->get($clave);

                $etiqueta = match ($granularidad) {
                    'semana' => 'Sem '.substr($clave, -2),
                    'mes' => CarbonImmutable::createFromFormat('!Y-m', $clave)->format('m/Y'),
                    default => CarbonImmutable::createFromFormat('!Y-m-d', $clave)->format('d/m'),
                };

                return [
                    'periodo' => $clave,
                    'etiqueta' => $etiqueta,
                    'total' => round((float) ($fila->total ?? 0), 2),
                    'cantidad' => (int) ($fila->cantidad ?? 0),
                ];
            })
            ->all();

        return [
            'productos' => $productos,
            'ventas' => $ventas,
            'granularidad' => $granularidad,
        ];
    }

    private function cajaAbiertaDelUsuario(User $user): ?Caja
    {
        return Caja::query()
            ->where('negocio_id', $user->negocio_id)
            ->where('user_id', $user->id)
            ->abiertas()
            ->latest('fecha_apertura')
            ->first();
    }

    /**
     * Users available for the cash register filter.
     *
     * @return Collection<int, User>
     */
    private function usuariosDisponibles(?Negocio $negocio, ?User $user, bool $esCajero): Collection
    {
        if ($negocio === null || $user === null) {
            return collect();
        }

        if ($esCajero) {
            return collect([$user]);
        }

        return $negocio->usuarios()->orderBy('name')->get();
    }
}
