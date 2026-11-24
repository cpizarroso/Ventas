@php
    $prefijo = $prefijo ?? 'detalle';
    $mostrarCerrar = $mostrarCerrar ?? false;
@endphp

<div class="flex min-h-0 flex-1 flex-col">
    <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-4 py-3 dark:border-stone-700/60">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-stone-900 dark:text-stone-100">Detalle de la venta</p>
            <p class="text-xs text-stone-500 dark:text-stone-400">
                {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} en total
            </p>
        </div>

        @if ($mostrarCerrar)
            <button
                type="button"
                x-on:click="carritoAbierto = false"
                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-600 hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                aria-label="Cerrar detalle de la venta"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        @endif
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
        @if ($lineasCarrito->isEmpty() && $lineasPromociones->isEmpty())
            <div class="flex h-full min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                <div class="flex size-12 items-center justify-center rounded-xl bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 4h2l2.4 11.2A1 1 0 0 0 8.4 16h9.2a1 1 0 0 0 1-.8L20 7H6" />
                        <circle cx="9" cy="20" r="1" />
                        <circle cx="17" cy="20" r="1" />
                    </svg>
                </div>
                <p class="mt-3 text-sm font-medium text-stone-600 dark:text-stone-300">El detalle está vacío</p>
                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">
                    Toca un producto o promoción para agregarlo a la venta.
                </p>
            </div>
        @else
            @if ($paraLlevar)
                <div class="mb-3 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
                    <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                        <path d="M3 6h18M16 10a4 4 0 0 1-8 0" />
                    </svg>
                    <p class="text-xs font-semibold">
                        Para llevar
                        <span class="font-normal">— No se consume en el local.</span>
                    </p>
                </div>
            @endif

            @if ($hayAdvertenciasStock)
                <div class="mb-3 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 dark:border-amber-700/60 dark:bg-amber-900/20">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Stock insuficiente</p>
                    <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                        Algunos ítems superan el stock disponible. La venta se puede cobrar igual y el stock quedará negativo.
                    </p>
                </div>
            @endif

            <ul class="divide-y divide-stone-100 dark:divide-stone-700/60" role="list">
                @foreach ($lineasCarrito as $linea)
                    <li wire:key="{{ $prefijo }}-linea-{{ $linea['producto']->id }}" class="py-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex min-w-[10rem] flex-1 items-center gap-2">
                                <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                                    @if ($linea['producto']->imagenUrl())
                                        <img src="{{ $linea['producto']->imagenUrl() }}" alt="{{ $linea['producto']->nombre }}" class="size-full object-cover" loading="lazy">
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="line-clamp-2 break-words text-sm font-bold text-stone-900 dark:text-white" title="{{ $linea['producto']->nombre }}">
                                        {{ $linea['producto']->nombre }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                        Bs {{ number_format($linea['precio_unitario'], 2) }} c/u
                                    </p>
                                </div>
                            </div>

                            <div class="ml-auto flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="disminuirCarrito({{ $linea['producto']->id }})"
                                    aria-label="Disminuir cantidad de {{ $linea['producto']->nombre }}"
                                    class="flex size-8 items-center justify-center rounded-lg border border-stone-300 text-base font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                                >
                                    &minus;
                                </button>
                                <span class="w-7 text-center text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100" aria-live="polite">
                                    {{ $linea['cantidad'] }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="incrementarCarrito({{ $linea['producto']->id }})"
                                    aria-label="Aumentar cantidad de {{ $linea['producto']->nombre }}"
                                    class="flex size-8 items-center justify-center rounded-lg bg-amber-500 text-base font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                >
                                    +
                                </button>
                                <button
                                    type="button"
                                    wire:click="quitarDelCarrito({{ $linea['producto']->id }})"
                                    aria-label="Quitar {{ $linea['producto']->nombre }} del detalle"
                                    title="Quitar ítem"
                                    class="ml-1 flex size-8 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/40"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @if ($linea['producto']->stock !== null && $linea['cantidad'] > $linea['producto']->stock)
                            <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400" role="status">
                                Supera el stock; quedarán {{ $linea['producto']->stock - $linea['cantidad'] }} unidades.
                            </p>
                        @endif

                        <p class="mt-1 text-right text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100">
                            Bs {{ number_format($linea['subtotal'], 2) }}
                        </p>
                    </li>
                @endforeach

                @foreach ($lineasPromociones as $linea)
                    @php
                        $promocion = $linea['promocion'];
                        $primerProducto = $promocion->productos->first();
                        $stockInsuficiente = $linea['componentes']->contains(
                            fn (array $componente) => $componente['producto']->stock !== null
                                && $componente['cantidad'] * $linea['cantidad'] > $componente['producto']->stock
                        );
                    @endphp

                    <li wire:key="{{ $prefijo }}-promocion-{{ $promocion->id }}" class="py-3">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="flex min-w-[10rem] flex-1 items-center gap-2">
                                <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-amber-100 text-amber-500 dark:bg-amber-900/40 dark:text-amber-300">
                                    @if ($primerProducto?->imagenUrl())
                                        <img src="{{ $primerProducto->imagenUrl() }}" alt="{{ $promocion->nombre }}" class="size-full object-cover" loading="lazy">
                                    @else
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M20 12v9H4v-9M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7ZM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7Z" />
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="line-clamp-2 break-words text-sm font-bold text-stone-900 dark:text-white" title="{{ $promocion->nombre }}">
                                        {{ $promocion->nombre }}
                                        <span class="ml-1 inline-block rounded-full bg-amber-100 px-1.5 py-0.5 align-middle text-[10px] font-bold uppercase tracking-wide text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">Promoción</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                        Bs {{ number_format($linea['precio_unitario'], 2) }} por combo
                                    </p>
                                    <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                        Incluye:
                                        @foreach ($linea['componentes'] as $componente)
                                            <span class="inline-block rounded bg-stone-100 px-1 py-0.5 font-medium text-stone-800 dark:bg-stone-700 dark:text-stone-100">{{ $componente['cantidad'] }}× {{ $componente['producto']->nombre }}</span>
                                        @endforeach
                                    </p>
                                </div>
                            </div>

                            <div class="ml-auto flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    wire:click="disminuirPromocion({{ $promocion->id }})"
                                    aria-label="Disminuir cantidad de la promoción {{ $promocion->nombre }}"
                                    class="flex size-8 items-center justify-center rounded-lg border border-stone-300 text-base font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                                >
                                    &minus;
                                </button>
                                <span class="w-7 text-center text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100" aria-live="polite">
                                    {{ $linea['cantidad'] }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="incrementarPromocion({{ $promocion->id }})"
                                    aria-label="Aumentar cantidad de la promoción {{ $promocion->nombre }}"
                                    class="flex size-8 items-center justify-center rounded-lg bg-amber-500 text-base font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                >
                                    +
                                </button>
                                <button
                                    type="button"
                                    wire:click="quitarPromocion({{ $promocion->id }})"
                                    aria-label="Quitar la promoción {{ $promocion->nombre }} del detalle"
                                    title="Quitar promoción"
                                    class="ml-1 flex size-8 items-center justify-center rounded-lg text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/40"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @if ($stockInsuficiente)
                            <p class="mt-1 text-xs font-medium text-amber-700 dark:text-amber-400" role="status">
                                Algún ítem de la promoción supera el stock disponible; el stock quedará negativo.
                            </p>
                        @endif

                        <p class="mt-1 text-right text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100">
                            Bs {{ number_format($linea['subtotal'], 2) }}
                        </p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="border-t border-stone-100 px-4 py-3 dark:border-stone-700/60">
        @error('carrito')
            <p class="mb-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror

        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-medium text-stone-500 dark:text-stone-400">
                    {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }}
                </p>
                <p class="text-xl font-bold text-stone-900 tabular-nums dark:text-stone-100">
                    Bs {{ $totalCarrito }}
                </p>
            </div>

            <button
                type="button"
                wire:click="abrirModalPago"
                x-on:click="carritoAbierto = false"
                @disabled($cajaAbierta === null || ($lineasCarrito->isEmpty() && $lineasPromociones->isEmpty()))
                class="shrink-0 rounded-xl bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 dark:focus:ring-offset-stone-800"
            >
                Pagar
            </button>
        </div>

        @if ($lineasCarrito->isNotEmpty() || $lineasPromociones->isNotEmpty())
            <div class="mt-2 flex justify-center border-t border-stone-100 pt-2 dark:border-stone-700/60">
                <button
                    type="button"
                    wire:click="pedirCancelacion"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-stone-500 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 dark:text-stone-400 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    Cancelar venta
                </button>
            </div>
        @endif
    </div>
</div>
