<?php

namespace App\Http\Controllers;

use App\Enums\EstadoCaja;
use App\Models\Caja;
use App\Models\Negocio;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ? redirect()->route('ventas.index')->with('status', 'Caja abierta correctamente.')
            : redirect()->route('cajas.show', $caja)->with('status', 'Caja abierta correctamente.');
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
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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
        $cajas = $filtros['consulta']
            ->with(['user', 'cerradaPor'])
            ->orderBy('fecha_apertura')
            ->orderBy('id')
            ->get();

        $nombre = 'reporte-cajas-'.$filtros['desde']->format('Y-m-d').'_'.$filtros['hasta']->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($cajas): void {
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

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
