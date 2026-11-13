<x-layouts.app>
    <div class="mb-6">
        <a href="{{ route('cajas.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
            </svg>
            Volver a cajas
        </a>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Caja #{{ $caja->id }}</h1>
                    <span @class([
                        'rounded-full px-2 py-1 text-xs font-semibold',
                        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $esAbierta,
                        'bg-stone-100 text-stone-700 dark:bg-stone-700 dark:text-stone-200' => ! $esAbierta,
                    ])>
                        {{ $caja->estado->etiqueta() }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Abierta por {{ $caja->user?->name ?? '—' }} el {{ $caja->fecha_apertura?->format('d/m/Y H:i') }}
                </p>
            </div>

            @if ($esAbierta)
                <a href="{{ route('cajas.cerrar', $caja) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                    Cerrar caja
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

    <div class="space-y-6">
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Totales de la caja">
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Monto inicial</p>
                <p class="mt-1 break-words text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-xl">Bs {{ number_format((float) $caja->monto_inicial, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ventas</p>
                <p class="mt-1 break-words text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-xl">{{ $resumen['cantidad'] }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Total vendido</p>
                <p class="mt-1 break-words text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100 sm:text-xl">Bs {{ number_format($resumen['total'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Efectivo</p>
                <p class="mt-1 break-words text-lg font-bold text-emerald-900 tabular-nums dark:text-emerald-200 sm:text-xl">Bs {{ number_format($resumen['efectivo'], 2) }}</p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" aria-label="Cierre de caja">
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-400">Cobrado por QR</p>
                <p class="mt-1 text-lg font-bold text-sky-900 tabular-nums dark:text-sky-200">Bs {{ number_format($resumen['qr'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Efectivo esperado</p>
                <p class="mt-1 text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">Bs {{ number_format((float) ($esAbierta ? $montoEsperado : $caja->monto_esperado_efectivo), 2) }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Efectivo contado</p>
                <p class="mt-1 text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">
                    {{ $esAbierta ? '—' : 'Bs '.number_format((float) $caja->monto_cierre, 2) }}
                </p>
            </div>
            <div @class([
                'rounded-2xl border p-4',
                'border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800' => $esAbierta || $caja->diferencia === null,
                'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' => ! $esAbierta && $caja->diferencia !== null && (float) $caja->diferencia >= 0,
                'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' => ! $esAbierta && $caja->diferencia !== null && (float) $caja->diferencia < 0,
            ])>
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Diferencia</p>
                <p class="mt-1 text-lg font-bold tabular-nums text-stone-900 dark:text-stone-100">
                    {{ $esAbierta || $caja->diferencia === null ? '—' : 'Bs '.number_format((float) $caja->diferencia, 2) }}
                </p>
            </div>
        </section>

        @if ($esAbierta === false)
            <section class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6" aria-label="Datos del cierre">
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Datos del cierre</h2>
                <dl class="mt-4 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Cerrada por</dt>
                        <dd class="mt-1 text-stone-800 dark:text-stone-100">{{ $caja->cerradaPor?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Fecha de cierre</dt>
                        <dd class="mt-1 text-stone-800 dark:text-stone-100">{{ $caja->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Observaciones de apertura</dt>
                        <dd class="mt-1 text-stone-800 dark:text-stone-100">{{ $caja->observaciones_apertura ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Observaciones de cierre</dt>
                        <dd class="mt-1 text-stone-800 dark:text-stone-100">{{ $caja->observaciones_cierre ?: '—' }}</dd>
                    </div>
                </dl>
            </section>
        @endif

        <section class="rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800" aria-label="Ventas de la caja">
            <div class="border-b border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Ventas de la caja</h2>
            </div>

            @if ($ventas->isEmpty())
                <div class="px-4 py-12 text-center sm:px-6">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Esta caja todavía no tiene ventas</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                        <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                            <tr>
                                <th scope="col" class="px-4 py-3 sm:px-6">Venta</th>
                                <th scope="col" class="px-4 py-3">Fecha</th>
                                <th scope="col" class="px-4 py-3">Vendedor</th>
                                <th scope="col" class="px-4 py-3">Pago</th>
                                <th scope="col" class="px-4 py-3 text-right">Ítems</th>
                                <th scope="col" class="px-4 py-3 text-right sm:px-6">Total</th>
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
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                        {{ $venta->metodo_pago?->etiqueta() ?? 'Efectivo' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-stone-600 tabular-nums dark:text-stone-300">
                                        {{ $venta->detalles->sum('cantidad') }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-stone-900 tabular-nums dark:text-stone-100 sm:px-6">
                                        Bs {{ number_format((float) $venta->total, 2) }}
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
    </div>
</x-layouts.app>