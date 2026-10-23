<div @class(['pb-24' => $negocio !== null && ! $mostrarDetalle && $cantidadItems > 0])>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($ventaParaImprimirId !== null)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-venta-registrada"
            wire:keydown.escape.window="omitirImpresion"
        >
            <div class="w-full max-w-sm rounded-2xl border border-stone-200 bg-white p-5 shadow-xl dark:border-stone-700 dark:bg-stone-800 sm:p-6">
                <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9V4h12v5" />
                        <path d="M6 18H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-2" />
                        <path d="M6 14h12v6H6z" />
                    </svg>
                </div>

                <h2 id="titulo-venta-registrada" class="mt-4 text-lg font-semibold text-stone-900 dark:text-stone-100">
                    Venta #{{ $ventaParaImprimirId }} registrada
                </h2>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                    ¿Deseas imprimir el detalle de esta venta?
                </p>
                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                    La venta ya quedó guardada. Imprimir el ticket es opcional.
                </p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        wire:click="omitirImpresion"
                        class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                    >
                        Ahora no
                    </button>
                    <a
                        href="{{ route('ventas.ticket', ['venta' => $ventaParaImprimirId, 'imprimir' => 1]) }}"
                        target="_blank"
                        rel="noopener"
                        wire:click="omitirImpresion"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                    >
                        Imprimir ticket
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if ($negocio === null)
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l1.5-5h15L21 9" />
                    <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Tu usuario no tiene un negocio asignado</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                Contacta al superadministrador para que te asigne a un negocio y puedas empezar a vender.
            </p>
        </div>
    @elseif ($mostrarDetalle)
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                    Detalle de venta
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">
                        Ítems consumidos
                    </h2>
                    <span class="shrink-0 text-xs text-stone-500 dark:text-stone-400">
                        {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }}
                    </span>
                </div>

                @if ($lineasCarrito->isEmpty())
                    <div class="mt-4 rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">El detalle está vacío</p>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">
                            Vuelve a las categorías y acepta los ítems consumidos.
                        </p>
                        <button
                            type="button"
                            wire:click="volverACategorias"
                            class="mt-4 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400"
                        >
                            Ir a categorías
                        </button>

                        @error('carrito')
                            <p class="mt-3 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <ul class="mt-2 divide-y divide-stone-100 dark:divide-stone-700/60" role="list">
                        @foreach ($lineasCarrito as $linea)
                            <li wire:key="detalle-{{ $linea['producto']->id }}" class="py-3">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-stone-800 dark:text-stone-100">
                                            {{ $linea['producto']->nombre }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                            {{ $linea['producto']->categoria?->nombre }}
                                            · Bs {{ number_format($linea['precio_unitario'], 2) }} c/u
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="disminuirCarrito({{ $linea['producto']->id }})"
                                            @disabled($linea['cantidad'] === 0)
                                            aria-label="Disminuir cantidad de {{ $linea['producto']->nombre }}"
                                            class="flex size-9 items-center justify-center rounded-xl border border-stone-300 text-lg font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                                        >
                                            &minus;
                                        </button>
                                        <span class="w-8 text-center text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100" aria-live="polite">
                                            {{ $linea['cantidad'] }}
                                        </span>
                                        <button
                                            type="button"
                                            wire:click="incrementarCarrito({{ $linea['producto']->id }})"
                                            aria-label="Aumentar cantidad de {{ $linea['producto']->nombre }}"
                                            class="flex size-9 items-center justify-center rounded-xl bg-amber-500 text-lg font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        >
                                            +
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="quitarDelCarrito({{ $linea['producto']->id }})"
                                            aria-label="Quitar {{ $linea['producto']->nombre }} del detalle"
                                            title="Quitar ítem"
                                            class="flex size-9 items-center justify-center rounded-xl text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/40"
                                        >
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
                                            </svg>
                                        </button>
                                    </div>

                                    <span class="w-full shrink-0 text-right text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100 sm:w-24">
                                        Bs {{ number_format($linea['subtotal'], 2) }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-700/60 dark:bg-amber-900/20">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Total</span>
                            <span class="text-xl font-bold text-amber-900 tabular-nums dark:text-amber-200">Bs {{ $totalCarrito }}</span>
                        </div>

                        @error('carrito')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror

                        <div class="mt-3 rounded-xl border border-stone-200 bg-white p-3 dark:border-stone-600 dark:bg-stone-800">
                            <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Forma de pago</p>

                            <div class="mt-2 grid grid-cols-2 gap-2" role="group" aria-label="Forma de pago">
                                <button
                                    type="button"
                                    wire:click="seleccionarMetodoPago('efectivo')"
                                    aria-pressed="{{ $metodoPago === 'efectivo' ? 'true' : 'false' }}"
                                    @class([
                                        'rounded-xl border px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                        'border-amber-500 bg-amber-500 text-stone-900' => $metodoPago === 'efectivo',
                                        'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $metodoPago !== 'efectivo',
                                    ])
                                >
                                    Efectivo
                                </button>
                                <button
                                    type="button"
                                    wire:click="seleccionarMetodoPago('qr')"
                                    aria-pressed="{{ $metodoPago === 'qr' ? 'true' : 'false' }}"
                                    @class([
                                        'rounded-xl border px-3 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                        'border-amber-500 bg-amber-500 text-stone-900' => $metodoPago === 'qr',
                                        'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $metodoPago !== 'qr',
                                    ])
                                >
                                    QR
                                </button>
                            </div>

                            @error('metodoPago')
                                <p class="mt-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror

                            @if ($metodoPago === 'efectivo')
                                <div class="mt-3">
                                    <label for="montoRecibido" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                        Efectivo recibido
                                    </label>
                                    <input
                                        type="number"
                                        id="montoRecibido"
                                        wire:model.live.debounce.250ms="montoRecibido"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-right text-lg font-semibold text-stone-900 tabular-nums focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
                                    >

                                    @error('montoRecibido')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                    @enderror

                                    <div class="mt-2 flex items-center justify-between rounded-lg bg-stone-100 px-3 py-2 dark:bg-stone-900/60">
                                        <span class="text-sm font-medium text-stone-600 dark:text-stone-300">Cambio a devolver</span>
                                        <span class="text-lg font-bold text-emerald-700 tabular-nums dark:text-emerald-400">Bs {{ $cambio }}</span>
                                    </div>

                                    @if ($montoRecibido !== null && $montoRecibido !== '' && ! $efectivoSuficiente)
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">
                                            El efectivo recibido no cubre el total.
                                        </p>
                                    @endif
                                </div>
                            @elseif ($metodoPago === 'qr')
                                <p class="mt-3 rounded-lg bg-stone-100 px-3 py-2 text-xs text-stone-600 dark:bg-stone-900/60 dark:text-stone-300">
                                    El cliente paga el total de Bs {{ $totalCarrito }} por QR.
                                </p>
                            @else
                                <p class="mt-3 text-xs text-stone-400 dark:text-stone-500">
                                    Selecciona cómo pagará el cliente para continuar.
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                wire:click="vaciarCarrito"
                                class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700"
                            >
                                Vaciar
                            </button>
                            <button
                                type="button"
                                wire:click="volverACategorias"
                                class="ml-auto rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                            >
                                Seguir agregando
                            </button>
                            <button
                                type="button"
                                wire:click="cobrar"
                                wire:loading.attr="disabled"
                                wire:target="cobrar"
                                @disabled(! $puedeCobrar)
                                class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 dark:focus:ring-offset-stone-800"
                            >
                                Cobrar Bs {{ $totalCarrito }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @elseif ($categoriaSeleccionada !== null)
        <div class="mx-auto w-full max-w-2xl">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <button
                    type="button"
                    wire:click="deseleccionar"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-medium text-stone-500 hover:bg-stone-200 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
                    </svg>
                    Volver a categorías
                </button>
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">
                    {{ $categoriaSeleccionada->nombre }}
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
                <div class="flex items-baseline justify-between gap-3">
                    <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">
                        Ítems de «{{ $categoriaSeleccionada->nombre }}»
                    </h2>
                    <span class="shrink-0 text-xs text-stone-500 dark:text-stone-400">
                        {{ $productos->count() }} {{ $productos->count() === 1 ? 'ítem' : 'ítems' }}
                    </span>
                </div>

                @if ($productos->isEmpty())
                    <div class="mt-4 rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                        <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Esta categoría todavía no tiene ítems</p>
                        @if ($puedeConfigurar)
                            <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">
                                Agrégalos desde configuración para poder venderlos.
                            </p>
                            <a href="{{ route('configuracion.index') }}"
                               class="mt-4 inline-block rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400">
                                Ir a configuración
                            </a>
                        @endif
                    </div>
                @else
                    <ul class="mt-2 divide-y divide-stone-100 dark:divide-stone-700/60" role="list">
                        @foreach ($productos as $producto)
                            <li wire:key="producto-{{ $producto->id }}" class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-stone-800 dark:text-stone-100">
                                        {{ $producto->nombre }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                        Bs {{ number_format((float) $producto->precio, 2) }} c/u
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="disminuir({{ $producto->id }})"
                                        @disabled($cantidadesPorProducto[$producto->id] === 0)
                                        aria-label="Disminuir cantidad de {{ $producto->nombre }}"
                                        class="flex size-9 items-center justify-center rounded-xl border border-stone-300 text-lg font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700 dark:disabled:hover:bg-transparent"
                                    >
                                        &minus;
                                    </button>
                                    <span class="w-8 text-center text-sm font-semibold text-stone-900 tabular-nums dark:text-stone-100" aria-live="polite">
                                        {{ $cantidadesPorProducto[$producto->id] }}
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="incrementar({{ $producto->id }})"
                                        aria-label="Aumentar cantidad de {{ $producto->nombre }}"
                                        class="flex size-9 items-center justify-center rounded-xl bg-amber-500 text-lg font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    >
                                        +
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-700/60 dark:bg-amber-900/20">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="block text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Total seleccionado</span>
                                <span class="block text-lg font-bold text-amber-900 tabular-nums dark:text-amber-200">Bs {{ $total }}</span>
                            </div>
                            <button
                                type="button"
                                wire:click="aceptar"
                                @disabled(! $haySeleccion)
                                class="rounded-xl bg-stone-900 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white hover:bg-stone-700 focus:outline-none focus:ring-2 focus:ring-stone-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-amber-500 dark:text-stone-900 dark:hover:bg-amber-400 dark:focus:ring-amber-500 dark:focus:ring-offset-stone-800"
                            >
                                Aceptar
                            </button>
                        </div>

                        @error('cantidades')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
        </div>
    @elseif ($categorias->isEmpty())
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1.5" />
                    <rect x="14" y="3" width="7" height="7" rx="1.5" />
                    <rect x="3" y="14" width="7" height="7" rx="1.5" />
                    <rect x="14" y="14" width="7" height="7" rx="1.5" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Todavía no hay categorías creadas</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                @if ($puedeConfigurar)
                    Crea la primera categoría desde la pantalla de configuración para empezar a vender.
                @else
                    Avisa al administrador del negocio para que cree las categorías de venta.
                @endif
            </p>
            @if ($puedeConfigurar)
                <a href="{{ route('configuracion.index') }}"
                   class="mt-6 inline-block rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                    Ir a configuración
                </a>
            @endif
        </div>
    @else
        <div class="flex flex-wrap justify-center gap-3 sm:gap-4">
            @foreach ($categorias as $categoria)
                <button
                    type="button"
                    wire:click="seleccionar({{ $categoria->id }})"
                    class="group flex min-h-28 w-[calc(50%-0.375rem)] flex-col items-center justify-center gap-2 rounded-2xl border border-stone-200 bg-white p-4 text-center shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 active:translate-y-0 dark:border-stone-700 dark:bg-stone-800 dark:hover:border-amber-500/60 dark:focus:ring-offset-stone-800 sm:min-h-32 sm:w-[calc(33.333%-0.667rem)] md:w-[calc(25%-0.75rem)] lg:w-[calc(20%-0.8rem)]"
                >
                    <span class="flex size-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700 transition group-hover:bg-amber-500 group-hover:text-stone-900 dark:bg-amber-900/40 dark:text-amber-300 dark:group-hover:bg-amber-500 dark:group-hover:text-stone-900" aria-hidden="true">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l1.5-5h15L21 9" />
                            <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                            <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                        </svg>
                    </span>
                    <span class="text-sm font-semibold text-stone-800 dark:text-stone-100">{{ $categoria->nombre }}</span>
                </button>
            @endforeach
        </div>
    @endif

    @if ($negocio !== null && ! $mostrarDetalle && $cantidadItems > 0)
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-stone-700 dark:bg-stone-800/95">
            <div class="mx-auto flex w-full max-w-2xl items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium text-stone-500 dark:text-stone-400">
                        {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} en el detalle
                    </p>
                    <p class="text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">Bs {{ $totalCarrito }}</p>
                </div>
                <button
                    type="button"
                    wire:click="verDetalle"
                    class="shrink-0 rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                >
                    Ver detalle
                </button>
            </div>
        </div>
    @endif
</div>
