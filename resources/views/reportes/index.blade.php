<x-layouts.app>
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
                <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Reportes de ventas</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    {{ $puedeFiltrarPorCajero
                        ? 'Ventas por rango de fechas y cajero, con detalle, promociones y resumen por producto.'
                        : 'Tus ventas por rango de fechas, con detalle, promociones y resumen por producto.' }}
                </p>
            </div>
        </div>
    </div>

    @if ($negocio === null)
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin negocio asignado</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                Tu usuario no tiene un negocio asignado, por lo que no hay reportes disponibles.
            </p>
        </div>
    @else
        <div class="space-y-6">
            <section class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6" aria-label="Filtros del reporte">
                <form method="GET" action="{{ route('reportes.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="w-full sm:w-72">
                        <label for="busqueda" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Buscar</label>
                        <input type="search" id="busqueda" name="busqueda" value="{{ old('busqueda', $busqueda) }}"
                               placeholder="N° ticket, cliente, vendedor o glosa…"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100">
                        @error('busqueda')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="w-full sm:w-auto">
                        <label for="desde" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Desde</label>
                        <input type="date" id="desde" name="desde" value="{{ old('desde', request('desde', $desde->format('Y-m-d'))) }}"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:[color-scheme:dark] sm:w-auto">
                        @error('desde')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="w-full sm:w-auto">
                        <label for="hasta" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Hasta</label>
                        <input type="date" id="hasta" name="hasta" value="{{ old('hasta', request('hasta', $hasta->format('Y-m-d'))) }}"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:[color-scheme:dark] sm:w-auto">
                        @error('hasta')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($puedeFiltrarPorCajero)
                        <div class="w-full sm:w-auto">
                            <label for="cajero_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Cajero</label>
                            <select id="cajero_id" name="cajero_id"
                                    class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:[color-scheme:dark] sm:w-auto">
                                <option value="">Todos los cajeros</option>
                                @foreach ($cajeros as $cajero)
                                    <option value="{{ $cajero->id }}" @selected((string) old('cajero_id', $cajeroId) === (string) $cajero->id)>
                                        {{ $cajero->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cajero_id')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <button type="submit"
                            class="w-full rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-500 dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400 sm:w-auto">
                        Aplicar
                    </button>
                </form>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-stone-50 px-4 py-3 dark:bg-stone-900/40">
                    <p class="text-sm text-stone-600 dark:text-stone-300">
                        Período:
                        <span class="font-semibold text-stone-900 dark:text-stone-100">{{ $desde->format('d/m/Y') }} — {{ $hasta->format('d/m/Y') }}</span>
                        <span class="mx-1 text-stone-300 dark:text-stone-600">·</span>
                        @if ($cajeroSeleccionado !== null)
                            {{ $puedeFiltrarPorCajero ? 'Cajero:' : 'Ventas de:' }}
                            <span class="font-semibold text-stone-900 dark:text-stone-100">{{ $cajeroSeleccionado->name }}</span>
                        @else
                            <span class="font-semibold text-stone-900 dark:text-stone-100">Todos los cajeros</span>
                        @endif
                    </p>
                    <a href="{{ route('reportes.export', array_filter([
                        'desde' => request('desde'),
                        'hasta' => request('hasta'),
                        'cajero_id' => $puedeFiltrarPorCajero ? request('cajero_id') : null,
                        'busqueda' => request('busqueda'),
                    ])) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-900/30 sm:w-auto">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                        </svg>
                        Exportar CSV
                    </a>
                </div>
            </section>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Resumen del período">
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Total vendido</p>
                    <p class="mt-1 break-words text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-2xl">Bs {{ number_format($totalVendido, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ventas</p>
                    <p class="mt-1 break-words text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-2xl">{{ $cantidadVentas }}</p>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ítems vendidos</p>
                    <p class="mt-1 break-words text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-2xl">{{ $itemsVendidos }}</p>
                </div>
                <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ticket promedio</p>
                    <p class="mt-1 break-words text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-2xl">Bs {{ number_format($ticketPromedio, 2) }}</p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2" aria-label="Resumen de caja">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Efectivo cobrado</p>
                    <p class="mt-1 break-words text-xl font-bold text-emerald-900 tabular-nums dark:text-emerald-200 sm:text-2xl">Bs {{ number_format($totalEfectivo, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-400">Cobrado por QR</p>
                    <p class="mt-1 break-words text-xl font-bold text-sky-900 tabular-nums dark:text-sky-200 sm:text-2xl">Bs {{ number_format($totalQr, 2) }}</p>
                </div>
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800" aria-label="Ventas del período">
                <div class="border-b border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Ventas</h2>
                </div>

                @if ($ventas->isEmpty())
                    <div class="px-4 py-12 text-center sm:px-6">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">No hay ventas en el período seleccionado</p>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Prueba con otro rango de fechas o cambia la búsqueda.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 sm:px-6">Venta</th>
                                    <th scope="col" class="px-4 py-3">Fecha</th>
                                    <th scope="col" class="px-4 py-3">Vendedor</th>
                                    <th scope="col" class="px-4 py-3">Tipo</th>
                                    <th scope="col" class="px-4 py-3">Para llevar</th>
                                    <th scope="col" class="px-4 py-3">Pago</th>
                                    <th scope="col" class="px-4 py-3 text-right">Ítems</th>
                                    <th scope="col" class="px-4 py-3 text-right">Total</th>
                                    <th scope="col" class="px-4 py-3 sm:px-6">Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                                @foreach ($ventas as $venta)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-stone-800 dark:text-stone-100 sm:px-6">
                                            #{{ $venta->numeroTicket() }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                            {{ $venta->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                            {{ $venta->user?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span @class([
                                                'rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-stone-100 text-stone-700 dark:bg-stone-700 dark:text-stone-200' => $venta->tipoReporte() === 'normal',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $venta->tipoReporte() === 'promocion',
                                                'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300' => $venta->tipoReporte() === 'mixta',
                                            ])>
                                                {{ $venta->etiquetaTipoReporte() }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @if ($venta->para_llevar)
                                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                    Para llevar
                                                </span>
                                            @else
                                                <span class="rounded-full bg-stone-100 px-2 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                                                    Local
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            <span @class([
                                                'rounded-full px-2 py-1 text-xs font-semibold',
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => ($venta->metodo_pago?->value ?? 'efectivo') === 'efectivo',
                                                'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' => $venta->metodo_pago?->value === 'qr',
                                            ])>
                                                {{ $venta->metodo_pago?->etiqueta() ?? 'Efectivo' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300">
                                            {{ $venta->detalles->sum('cantidad') }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-stone-900 tabular-nums dark:text-stone-100">
                                            Bs {{ number_format((float) $venta->total, 2) }}
                                        </td>
                                        <td class="px-4 py-3 sm:px-6">
                                            <details class="group">
                                                <summary class="cursor-pointer list-none text-xs font-semibold text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                                                    Ver ítems
                                                </summary>
                                                @if ($venta->para_llevar)
                                                    <p class="mt-1 rounded-lg bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                        Para llevar — No se consume en el local.
                                                    </p>
                                                @endif
                                                <ul class="mt-2 space-y-1 text-xs text-stone-600 dark:text-stone-300">
                                                    @foreach ($venta->detalles as $detalle)
                                                        <li class="flex justify-between gap-3">
                                                            <span class="min-w-0">
                                                                <span class="block truncate">
                                                                    @if ($detalle->es_promocion)
                                                                        <span class="mr-1 rounded bg-amber-100 px-1 py-0.5 text-[10px] font-bold uppercase text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Promo</span>
                                                                    @endif
                                                                    {{ $detalle->cantidad }} × {{ $detalle->nombre }}
                                                                </span>
                                                                @if ($detalle->es_promocion && $detalle->promocionItems->isNotEmpty())
                                                                    <span class="mt-0.5 block text-[11px] text-stone-400 dark:text-stone-500">
                                                                        Incluye: {{ $detalle->promocionItems->map(fn ($item) => $item->cantidad.'× '.$item->nombre)->implode(', ') }}
                                                                    </span>
                                                                @endif
                                                            </span>
                                                            <span class="shrink-0 tabular-nums">Bs {{ number_format((float) $detalle->subtotal, 2) }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                        {{ $ventas->links() }}
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800" aria-label="Resumen por producto">
                <div class="border-b border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Resumen por producto</h2>
                </div>

                @if ($resumenProductos->isEmpty())
                    <div class="px-4 py-12 text-center sm:px-6">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Sin productos vendidos en el período</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 sm:px-6">Producto</th>
                                    <th scope="col" class="px-4 py-3 text-right">Cantidad</th>
                                    <th scope="col" class="px-4 py-3 text-right">Stock actual</th>
                                    <th scope="col" class="px-4 py-3 text-right sm:px-6">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                                @foreach ($resumenProductos as $producto)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-stone-800 dark:text-stone-100 sm:px-6">
                                            {{ $producto->nombre }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300">
                                            {{ (int) $producto->cantidad }}
                                        </td>
                                        <td @class([
                                            'whitespace-nowrap px-4 py-3 text-right tabular-nums',
                                            'text-red-600 dark:text-red-400' => $producto->producto_actual_id !== null && $producto->stock !== null && (int) $producto->stock < 0,
                                            'text-stone-600 dark:text-stone-300' => $producto->producto_actual_id === null || $producto->stock === null || (int) $producto->stock >= 0,
                                        ])>
                                            @if ($producto->producto_actual_id === null)
                                                &mdash;
                                            @elseif ($producto->stock === null)
                                                Ilimitado
                                            @else
                                                {{ (int) $producto->stock }}
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-stone-900 tabular-nums dark:text-stone-100 sm:px-6">
                                            Bs {{ number_format((float) $producto->total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800" aria-label="Promociones vendidas">
                <div class="border-b border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Promociones vendidas</h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Cada combo cuenta como una unidad de venta.</p>
                </div>

                @if ($resumenPromociones->isEmpty())
                    <div class="px-4 py-12 text-center sm:px-6">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Sin promociones vendidas en el período</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 sm:px-6">Promoción</th>
                                    <th scope="col" class="px-4 py-3 text-right">Combos</th>
                                    <th scope="col" class="px-4 py-3 text-right sm:px-6">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                                @foreach ($resumenPromociones as $promocion)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-stone-800 dark:text-stone-100 sm:px-6">
                                            {{ $promocion->nombre }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300">
                                            {{ (int) $promocion->cantidad }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-stone-900 tabular-nums dark:text-stone-100 sm:px-6">
                                            Bs {{ number_format((float) $promocion->total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800" aria-label="Ítems incluidos en promociones">
                <div class="border-b border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Ítems incluidos en promociones</h2>
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Desglose físico de los productos entregados dentro de combos.</p>
                </div>

                @if ($itemsPromocion->isEmpty())
                    <div class="px-4 py-12 text-center sm:px-6">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Sin ítems promocionales en el período</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                                <tr>
                                    <th scope="col" class="px-4 py-3 sm:px-6">Producto</th>
                                    <th scope="col" class="px-4 py-3 text-right sm:px-6">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                                @foreach ($itemsPromocion as $item)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-stone-800 dark:text-stone-100 sm:px-6">
                                            {{ $item->nombre }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300 sm:px-6">
                                            {{ (int) $item->cantidad }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    @endif
</x-layouts.app>
