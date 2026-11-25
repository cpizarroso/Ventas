<x-layouts.app>
    @vite('resources/js/charts.js')

    <div class="mb-6">
        <a href="{{ route('ventas.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
            </svg>
            Volver a ventas
        </a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Cajas</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Aperturas, cierres y totales de caja por rango de fechas.
                </p>
            </div>
            @if ($cajaAbiertaActual !== null)
                <a href="{{ route('cajas.cerrar', $cajaAbiertaActual) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" />
                    </svg>
                    Cerrar caja
                </a>
            @else
                <a href="{{ route('cajas.create') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Abrir caja
                </a>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($negocio === null)
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin negocio asignado</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                Tu usuario no tiene un negocio asignado, por lo que no hay cajas disponibles.
            </p>
        </div>
    @else
        <div class="space-y-6">
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumen de cajas">
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Cajas</p>
                    <p class="mt-1 text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-2xl">{{ $resumen['cantidad'] }}</p>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Total vendido</p>
                    <p class="mt-1 break-words text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-2xl">Bs {{ number_format($resumen['total_ventas'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Efectivo</p>
                    <p class="mt-1 break-words text-xl font-bold text-emerald-900 tabular-nums dark:text-emerald-200 sm:text-2xl">Bs {{ number_format($resumen['total_efectivo'], 2) }}</p>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-400">QR</p>
                    <p class="mt-1 break-words text-xl font-bold text-sky-900 tabular-nums dark:text-sky-200 sm:text-2xl">Bs {{ number_format($resumen['total_qr'], 2) }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6" aria-label="Filtros de cajas">
                <form method="GET" action="{{ route('cajas.index') }}" class="flex flex-wrap items-end gap-3">
                    <x-calendario-rango
                        id="cajas-fechas"
                        etiqueta="Período"
                        :desde="old('desde', request('desde', $desde->format('Y-m-d')))"
                        :hasta="old('hasta', request('hasta', $hasta->format('Y-m-d')))"
                        class="w-full sm:w-80"
                    />
                    @error('desde')
                        <p class="w-full text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                    @error('hasta')
                        <p class="w-full text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="w-full sm:w-auto">
                        <x-select-tema
                            id="estado"
                            name="estado"
                            label="Estado"
                            :options="[
                                ['valor' => '', 'etiqueta' => 'Todas'],
                                ['valor' => 'abierta', 'etiqueta' => 'Abiertas'],
                                ['valor' => 'cerrada', 'etiqueta' => 'Cerradas'],
                            ]"
                            :selected="old('estado', $estado)"
                            class="w-full sm:w-auto"
                        />
                        @error('estado')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    @if (! $esCajero)
                        <div class="w-full sm:w-auto">
                            <x-select-tema
                                id="user_id"
                                name="user_id"
                                label="Usuario"
                                :options="array_merge(
                                    [['valor' => '', 'etiqueta' => 'Todos los usuarios']],
                                    $usuarios->map(fn ($usuario) => [
                                        'valor' => (string) $usuario->id,
                                        'etiqueta' => $usuario->name,
                                    ])->all(),
                                )"
                                :selected="old('user_id', $usuarioId)"
                                class="w-full sm:w-auto"
                            />
                            @error('user_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <button type="submit"
                            class="w-full rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-500 dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400 sm:w-auto">
                        Aplicar
                    </button>
                </form>

                @php($filtrosExportacion = array_filter([
                    'desde' => request('desde'),
                    'hasta' => request('hasta'),
                    'estado' => request('estado'),
                    'user_id' => $esCajero ? null : request('user_id'),
                ]))

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-stone-50 px-4 py-3 dark:bg-stone-900/40">
                    <p class="text-sm text-stone-600 dark:text-stone-300">
                        Período:
                        <span class="font-semibold text-stone-900 dark:text-stone-100">{{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}</span>
                    </p>
                    <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                        <a href="{{ route('cajas.export', $filtrosExportacion) }}"
                           data-no-loading
                           class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-900/30 sm:flex-none">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                            </svg>
                            Exportar CSV
                        </a>
                        <a href="{{ route('cajas.export.pdf', $filtrosExportacion) }}"
                           data-no-loading
                           class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-red-600 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-500 dark:text-red-400 dark:hover:bg-red-900/30 sm:flex-none">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6" />
                            </svg>
                            Exportar PDF
                        </a>
                    </div>
                </div>
            </section>

            @php($etiquetaGranularidad = match ($granularidadVentas) { 'semana' => 'por semana', 'mes' => 'por mes', default => 'por día' })

            <section class="grid gap-5 lg:grid-cols-2" aria-label="Gráficos del período">
                <div class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Productos vendidos</h2>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                Cantidad vendida de cada producto en el período seleccionado.
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            {{ count($graficoProductos) }} productos
                        </span>
                    </div>

                    @if (empty($graficoProductos))
                        <div class="mt-5 flex h-64 items-center justify-center rounded-xl border border-dashed border-stone-200 bg-stone-50 px-4 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900/30 dark:text-stone-400">
                            Sin productos vendidos en el período seleccionado.
                        </div>
                    @else
                        <div class="mt-5 max-h-[420px] overflow-y-auto pr-1">
                            <div class="relative" style="height: {{ max(280, count($graficoProductos) * 26) }}px;">
                                <canvas id="grafico-productos" role="img" aria-label="Cantidad vendida por producto"></canvas>
                            </div>
                        </div>
                    @endif

                    <script type="application/json" id="datos-grafico-productos">@json($graficoProductos)</script>
                </div>

                <div class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Ventas del período</h2>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                                Total vendido en Bs {{ $etiquetaGranularidad }}.
                            </p>
                        </div>
                        <span class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            Bs {{ number_format(array_sum(array_column($graficoVentas, 'total')), 2) }}
                        </span>
                    </div>

                    @if (empty($graficoVentas))
                        <div class="mt-5 flex h-64 items-center justify-center rounded-xl border border-dashed border-stone-200 bg-stone-50 px-4 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900/30 dark:text-stone-400">
                            Sin ventas en el período seleccionado.
                        </div>
                    @else
                        <div class="relative mt-5 h-72">
                            <canvas id="grafico-ventas" role="img" aria-label="Total de ventas por período"></canvas>
                        </div>
                    @endif

                    <script type="application/json" id="datos-grafico-ventas">@json($graficoVentas)</script>
                </div>
            </section>

            <section data-cajas-listado class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800" aria-label="Cajas del período">
                <div class="border-b border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Cajas registradas</h2>
                </div>

                @if ($cajas->isEmpty())
                    <div class="px-4 py-12 text-center sm:px-6">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">No hay cajas en el período seleccionado</p>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Abre una caja o prueba con otro rango de fechas.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 sm:px-6">Caja</th>
                                    <th scope="col" class="px-4 py-3">Estado</th>
                                    <th scope="col" class="px-4 py-3">Usuario</th>
                                    <th scope="col" class="px-4 py-3">Apertura</th>
                                    <th scope="col" class="px-4 py-3">Cierre</th>
                                    <th scope="col" class="px-4 py-3 text-right">Monto apertura</th>
                                    <th scope="col" class="px-4 py-3 text-right">Ventas</th>
                                    <th scope="col" class="px-4 py-3 text-right">Total vendido</th>
                                    <th scope="col" class="px-4 py-3 text-right">Total esperado</th>
                                    <th scope="col" class="px-4 py-3 text-right">Diferencia</th>
                                    <th scope="col" class="px-4 py-3 sm:px-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                                @foreach ($cajas as $caja)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-stone-800 dark:text-stone-100 sm:px-6">
                                            #{{ $caja->id }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span @class([
                                                'rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $caja->estaAbierta(),
                                                'bg-stone-100 text-stone-700 dark:bg-stone-700 dark:text-stone-200' => ! $caja->estaAbierta(),
                                            ])>
                                                {{ $caja->estado->etiqueta() }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                            {{ $caja->user?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                            {{ $caja->fecha_apertura?->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                            {{ $caja->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300">
                                            Bs {{ number_format((float) $caja->monto_inicial, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300">
                                            {{ $caja->cantidad_ventas }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-stone-900 tabular-nums dark:text-stone-100">
                                            Bs {{ number_format((float) $caja->total_ventas, 2) }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-emerald-700 tabular-nums dark:text-emerald-400">
                                            Bs {{ number_format($caja->montoEsperadoEfectivo(), 2) }}
                                        </td>
                                        <td @class([
                                            'whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums',
                                            'text-red-600 dark:text-red-400' => $caja->diferencia !== null && (float) $caja->diferencia < 0,
                                            'text-emerald-700 dark:text-emerald-400' => $caja->diferencia !== null && (float) $caja->diferencia >= 0,
                                            'text-stone-400 dark:text-stone-500' => $caja->diferencia === null,
                                        ])>
                                            @if ($caja->diferencia === null)
                                                —
                                            @else
                                                Bs {{ number_format((float) $caja->diferencia, 2) }}
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right sm:px-6">
                                            <a href="{{ route('cajas.show', $caja) }}"
                                               class="text-xs font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6" data-cajas-paginacion>
                        {{ $cajas->links('components.paginacion-compacta') }}
                    </div>
                @endif
            </section>
        </div>
    @endif

    <script>
        (() => {
            const cargarPagina = async (url, actualizarHistorial) => {
                try {
                    const respuesta = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

                    if (! respuesta.ok) {
                        window.location.href = url;
                        return;
                    }

                    const documento = new DOMParser().parseFromString(await respuesta.text(), 'text/html');
                    const contenedor = document.querySelector('[data-cajas-listado]');
                    const nuevoContenedor = documento.querySelector('[data-cajas-listado]');

                    if (! contenedor || ! nuevoContenedor) {
                        window.location.href = url;
                        return;
                    }

                    contenedor.replaceWith(nuevoContenedor);

                    if (actualizarHistorial) {
                        window.history.pushState({}, '', url);
                    }
                } catch (error) {
                    window.location.href = url;
                }
            };

            document.addEventListener('click', (event) => {
                const enlace = event.target.closest('[data-cajas-paginacion] a[href]');

                if (! enlace) {
                    return;
                }

                event.preventDefault();
                cargarPagina(enlace.href, true);
            });

            window.addEventListener('popstate', () => cargarPagina(window.location.href, false));
        })();
    </script>
</x-layouts.app>