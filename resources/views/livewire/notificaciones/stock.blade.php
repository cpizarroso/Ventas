<div class="relative" x-data="{ abierto: false }"
     x-on:click.outside="abierto = false"
     x-on:keydown.escape.window="abierto = false">
    <button
        type="button"
        x-on:click="abierto = ! abierto"
        x-bind:aria-expanded="abierto ? 'true' : 'false'"
        aria-label="Notificaciones de stock"
        title="Alertas de stock"
        class="relative rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
    >
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 0 0-5-5.9V4a1.5 1.5 0 0 0-3 0v1.1A6 6 0 0 0 4 11v3.2a2 2 0 0 1-.6 1.4L2 17h5m8 0a3 3 0 0 1-6 0" />
        </svg>
        @if ($total > 0)
            <span class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white ring-2 ring-white tabular-nums dark:ring-stone-800" aria-label="{{ $total }} alertas de stock">
                {{ $total > 9 ? '9+' : $total }}
            </span>
        @endif
    </button>

    <div
        x-show="abierto"
        x-cloak
        x-transition.opacity.duration.150ms
        role="dialog"
        aria-label="Alertas de stock"
        class="fixed inset-x-4 top-[4.5rem] z-50 max-h-[calc(100dvh-5rem)] overflow-y-auto overscroll-contain rounded-2xl border border-stone-200 bg-white p-3 shadow-xl dark:border-stone-700 dark:bg-stone-800 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-[min(24rem,calc(100vw-2rem))]"
    >
        <div class="mb-2 flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                Alertas de stock
            </p>
            <div class="flex items-center gap-1">
                @if ($total > 0)
                    <button
                        type="button"
                        wire:click="descartarTodo"
                        x-on:click="abierto = false"
                        class="rounded-lg px-2 py-1 text-[11px] font-semibold text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-stone-100"
                    >
                        Cerrar todo
                    </button>
                @endif
                <button
                    type="button"
                    x-on:click="abierto = false"
                    class="rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-stone-700 dark:hover:text-stone-200"
                    aria-label="Cerrar alertas de stock"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>
        </div>

        @if ($total === 0)
            <p class="px-1 py-4 text-center text-sm text-stone-500 dark:text-stone-400">
                No hay alertas de stock.
            </p>
        @else
            <div class="space-y-3">
                @if ($agotados->isNotEmpty())
                    <section aria-label="Productos con stock 0">
                        <p class="mb-1.5 text-xs font-semibold text-red-700 dark:text-red-400">
                            {{ $agotados->count() }} {{ $agotados->count() === 1 ? 'producto con stock 0' : 'productos con stock 0' }}
                        </p>
                        <ul class="divide-y divide-red-100 rounded-xl bg-red-50 px-3 dark:divide-red-900/40 dark:bg-red-900/20" role="list">
                            @foreach ($agotados as $alerta)
                                <li wire:key="alerta-agotado-{{ $alerta['id'] }}" class="flex items-center gap-2 py-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $alerta['nombre'] }}</p>
                                        <p class="text-[11px] text-red-700 dark:text-red-400">
                                            Stock: {{ $alerta['stock'] }} · Mín: {{ $alerta['min'] }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="descartar({{ $alerta['id'] }})"
                                        class="rounded-lg p-1 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:hover:bg-red-900/40"
                                        aria-label="Cerrar aviso de {{ $alerta['nombre'] }}"
                                        title="Cerrar aviso"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if ($bajos->isNotEmpty())
                    <section aria-label="Productos en stock mínimo">
                        <p class="mb-1.5 text-xs font-semibold text-amber-700 dark:text-amber-400">
                            {{ $bajos->count() }} {{ $bajos->count() === 1 ? 'producto en stock mínimo' : 'productos en stock mínimo' }}
                        </p>
                        <ul class="divide-y divide-amber-100 rounded-xl bg-amber-50 px-3 dark:divide-amber-900/40 dark:bg-amber-900/20" role="list">
                            @foreach ($bajos as $alerta)
                                <li wire:key="alerta-bajo-{{ $alerta['id'] }}" class="flex items-center gap-2 py-2">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $alerta['nombre'] }}</p>
                                        <p class="text-[11px] text-amber-700 dark:text-amber-400">
                                            Stock: {{ $alerta['stock'] }} · Mín: {{ $alerta['min'] }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="descartar({{ $alerta['id'] }})"
                                        class="rounded-lg p-1 text-amber-600 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-amber-900/40"
                                        aria-label="Cerrar aviso de {{ $alerta['nombre'] }}"
                                        title="Cerrar aviso"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif
    </div>
</div>
