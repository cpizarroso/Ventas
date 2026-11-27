<div>
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
                        <th scope="col" class="px-4 py-3 text-right">Total</th>
                        <th scope="col" class="px-4 py-3 text-right sm:px-6">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                    @foreach ($ventas as $venta)
                        <tr wire:key="venta-{{ $venta->id }}">
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
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold text-stone-900 tabular-nums dark:text-stone-100">
                                Bs {{ number_format((float) $venta->total, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right sm:px-6">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        wire:click="verDetalle({{ $venta->id }})"
                                        title="Ver detalle"
                                        aria-label="Ver detalle de la venta #{{ $venta->numeroTicket() }}"
                                        class="inline-flex size-8 items-center justify-center rounded-lg border border-stone-300 text-stone-600 hover:border-amber-400 hover:bg-amber-50 hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:text-stone-300 dark:hover:border-amber-700 dark:hover:bg-amber-900/20 dark:hover:text-amber-300"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M2.25 12s3.75-6.75 9.75-6.75 9.75 6.75 9.75 6.75-3.75 6.75-9.75 6.75S2.25 12 2.25 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                    <a
                                        href="{{ route('ventas.ticket', ['venta' => $venta->id, 'imprimir' => 1]) }}"
                                        target="_blank"
                                        rel="noopener"
                                        title="Reimprimir ticket"
                                        aria-label="Reimprimir ticket de la venta #{{ $venta->numeroTicket() }}"
                                        class="inline-flex size-8 items-center justify-center rounded-lg border border-stone-300 text-stone-600 hover:border-sky-400 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-stone-600 dark:text-stone-300 dark:hover:border-sky-700 dark:hover:bg-sky-900/20 dark:hover:text-sky-300"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 9V3h12v6" />
                                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                                            <path d="M6 14h12v7H6z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="border-t border-stone-100 px-4 py-4 dark:border-stone-700/60 sm:px-6">
            {{ $ventas->links('components.paginacion-compacta', ['enLivewire' => true]) }}
        </div>
    @endif

    @if ($ventaDetalle)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm"
             wire:click.self="cerrarDetalle">
            <div class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-xl dark:border-stone-700 dark:bg-stone-800"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="detalle-venta-titulo">
                <div class="flex items-start justify-between gap-3 border-b border-stone-100 px-4 py-3 dark:border-stone-700/60 sm:px-5">
                    <div class="min-w-0">
                        <h3 id="detalle-venta-titulo" class="truncate text-base font-semibold text-stone-900 dark:text-stone-100">
                            Detalle de la venta #{{ $ventaDetalle->numeroTicket() }}
                        </h3>
                        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                            {{ $ventaDetalle->created_at->format('d/m/Y H:i') }} · {{ $ventaDetalle->user?->name ?? '—' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="cerrarDetalle"
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-500 dark:hover:bg-stone-700 dark:hover:text-stone-200"
                        aria-label="Cerrar detalle"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4 sm:px-5">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Forma de pago</dt>
                            <dd class="mt-0.5 font-medium text-stone-800 dark:text-stone-100">
                                {{ $ventaDetalle->metodo_pago?->etiqueta() ?? 'Efectivo' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Cantidad de ítems</dt>
                            <dd class="mt-0.5 font-medium tabular-nums text-stone-800 dark:text-stone-100">
                                {{ $ventaDetalle->detalles->sum('cantidad') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Cliente</dt>
                            <dd class="mt-0.5 font-medium text-stone-800 dark:text-stone-100">{{ $ventaDetalle->cliente ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Tipo</dt>
                            <dd class="mt-0.5 font-medium text-stone-800 dark:text-stone-100">
                                {{ $ventaDetalle->para_llevar ? 'Para llevar' : 'En el local' }}
                            </dd>
                        </div>
                        @if ($ventaDetalle->monto_recibido !== null)
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Recibido</dt>
                                <dd class="mt-0.5 font-medium tabular-nums text-stone-800 dark:text-stone-100">
                                    Bs {{ number_format((float) $ventaDetalle->monto_recibido, 2) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Cambio</dt>
                                <dd class="mt-0.5 font-medium tabular-nums text-stone-800 dark:text-stone-100">
                                    Bs {{ number_format((float) $ventaDetalle->cambio, 2) }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    <h4 class="mt-5 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ítems vendidos</h4>
                    <ul class="mt-2 divide-y divide-stone-100 dark:divide-stone-700/60">
                        @forelse ($ventaDetalle->detalles as $detalle)
                            <li class="py-2.5">
                                <div class="flex items-baseline justify-between gap-3">
                                    <p class="min-w-0 truncate text-sm font-medium text-stone-800 dark:text-stone-100">
                                        {{ $detalle->nombre }}
                                        @if ($detalle->es_promocion)
                                            <span class="ml-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Promoción</span>
                                        @endif
                                    </p>
                                    <p class="shrink-0 text-sm font-semibold tabular-nums text-stone-900 dark:text-stone-100">
                                        Bs {{ number_format((float) $detalle->subtotal, 2) }}
                                    </p>
                                </div>
                                <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                    {{ $detalle->cantidad }} × Bs {{ number_format((float) $detalle->precio_unitario, 2) }}
                                </p>
                                @if ($detalle->es_promocion && $detalle->promocionItems->isNotEmpty())
                                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                        Incluye: {{ $detalle->promocionItems->map(fn ($item) => $item->nombre.' ×'.$item->cantidad)->implode(', ') }}
                                    </p>
                                @endif
                            </li>
                        @empty
                            <li class="py-4 text-center text-xs text-stone-500 dark:text-stone-400">Sin ítems registrados</li>
                        @endforelse
                    </ul>

                    @if ($ventaDetalle->glosa)
                        <div class="mt-4 rounded-xl bg-stone-50 px-3 py-2 text-xs text-stone-600 dark:bg-stone-900/40 dark:text-stone-300">
                            <span class="font-semibold">Glosa:</span> {{ $ventaDetalle->glosa }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-stone-100 px-4 py-3 dark:border-stone-700/60 sm:px-5">
                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Total de la venta</span>
                        <span class="text-lg font-bold tabular-nums text-stone-900 dark:text-stone-100">
                            Bs {{ number_format((float) $ventaDetalle->total, 2) }}
                        </span>
                    </div>
                    <a
                        href="{{ route('ventas.ticket', ['venta' => $ventaDetalle->id, 'imprimir' => 1]) }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-sky-300 bg-sky-50 px-3 py-2 text-xs font-bold text-sky-800 hover:bg-sky-100 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:border-sky-700 dark:bg-sky-900/30 dark:text-sky-200 dark:hover:bg-sky-900/50"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 9V3h12v6" />
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <path d="M6 14h12v7H6z" />
                        </svg>
                        Reimprimir
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
