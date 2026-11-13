<div
    class="pb-24 lg:pb-0"
    x-data="{ carritoAbierto: false, qrAmpliado: false }"
    x-on:keydown.escape.window="carritoAbierto = false; qrAmpliado = false"
>
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
                    Venta #{{ $ventaParaImprimirNumero ?? $ventaParaImprimirId }} registrada
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
    @else
        @if ($cajaAbierta === null)
            <div class="mb-4 flex flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200 sm:flex-row sm:items-center sm:justify-between" role="alert">
                <div>
                    <p class="font-semibold">No tienes una caja abierta.</p>
                    <p class="text-xs">Abre tu caja e ingresa el monto inicial antes de cobrar.</p>
                </div>
                <a href="{{ route('cajas.create', ['origen' => 'ventas']) }}" class="shrink-0 rounded-lg bg-amber-500 px-4 py-2 text-center text-xs font-bold uppercase tracking-wide text-stone-900 hover:bg-amber-400">
                    Abrir caja
                </a>
            </div>
        @else
            <div class="mb-4 flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 sm:flex-row sm:items-center sm:justify-between" role="status">
                <div>
                    <p class="font-semibold">Caja abierta desde {{ $cajaAbierta->fecha_apertura->format('d/m/Y H:i') }}.</p>
                    <p class="text-xs">Monto inicial: Bs {{ number_format((float) $cajaAbierta->monto_inicial, 2) }}</p>
                </div>
                <a href="{{ route('cajas.cerrar', $cajaAbierta) }}" class="shrink-0 rounded-lg border border-emerald-300 px-4 py-2 text-center text-xs font-bold uppercase tracking-wide text-emerald-800 hover:bg-emerald-100 dark:border-emerald-700 dark:text-emerald-200 dark:hover:bg-emerald-900/50">
                    Cerrar caja
                </a>
            </div>
        @endif

        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-6">
            <section class="min-w-0" aria-label="Productos">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-stone-400 dark:text-stone-500" aria-hidden="true">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </span>
                        <label for="busqueda" class="sr-only">Buscar productos</label>
                        <input
                            type="search"
                            id="busqueda"
                            wire:model.live.debounce.300ms="busqueda"
                            placeholder="Buscar por producto o categoría…"
                            class="w-full rounded-xl border border-stone-300 bg-white py-2.5 pl-10 pr-3 text-sm text-stone-900 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-800 dark:text-stone-100 dark:placeholder:text-stone-500"
                        >
                    </div>

                    <div class="flex shrink-0 items-center gap-1 self-start rounded-xl border border-stone-300 bg-white p-1 dark:border-stone-600 dark:bg-stone-800 sm:self-auto" role="group" aria-label="Vista de productos">
                        <button
                            type="button"
                            wire:click="cambiarVista('grid')"
                            aria-pressed="{{ $vista === 'grid' ? 'true' : 'false' }}"
                            aria-label="Vista de cuadrícula"
                            title="Cuadrícula"
                            @class([
                                'flex size-9 items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                'bg-amber-500 text-stone-900' => $vista === 'grid',
                                'text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-700' => $vista !== 'grid',
                            ])
                        >
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7" rx="1.5" />
                                <rect x="14" y="3" width="7" height="7" rx="1.5" />
                                <rect x="3" y="14" width="7" height="7" rx="1.5" />
                                <rect x="14" y="14" width="7" height="7" rx="1.5" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            wire:click="cambiarVista('lista')"
                            aria-pressed="{{ $vista === 'lista' ? 'true' : 'false' }}"
                            aria-label="Vista de lista"
                            title="Lista"
                            @class([
                                'flex size-9 items-center justify-center rounded-lg transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                'bg-amber-500 text-stone-900' => $vista === 'lista',
                                'text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-700' => $vista !== 'lista',
                            ])
                        >
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8 6h13M8 12h13M8 18h13" />
                                <path d="M3 6h.01M3 12h.01M3 18h.01" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="Filtrar por categoría">
                    <button
                        type="button"
                        wire:click="seleccionarCategoriaFiltro(null)"
                        aria-pressed="{{ $categoriaFiltroId === null ? 'true' : 'false' }}"
                        @class([
                            'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                            'border-amber-500 bg-amber-500 text-stone-900' => $categoriaFiltroId === null,
                            'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $categoriaFiltroId !== null,
                        ])
                    >
                        Todas
                    </button>

                    @foreach ($categorias as $categoria)
                        <button
                            type="button"
                            wire:key="chip-categoria-{{ $categoria->id }}"
                            wire:click="seleccionarCategoriaFiltro({{ $categoria->id }})"
                            aria-pressed="{{ $categoriaFiltroId === $categoria->id ? 'true' : 'false' }}"
                            @class([
                                'shrink-0 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                'border-amber-500 bg-amber-500 text-stone-900' => $categoriaFiltroId === $categoria->id,
                                'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $categoriaFiltroId !== $categoria->id,
                            ])
                        >
                            {{ $categoria->nombre }}
                        </button>
                    @endforeach
                </div>

                @if ($promociones->isNotEmpty())
                    <section class="mt-5" aria-label="Promociones disponibles">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Promociones de hoy</h2>
                            <span class="text-xs font-medium text-stone-400 dark:text-stone-500">Precio especial por combo</span>
                        </div>

                        <div class="mt-2 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($promociones as $promocion)
                                @php
                                    $enCarritoPromocion = $cantidadesPromocion[$promocion->id] ?? 0;
                                    $totalNormalPromocion = $promocion->productos->sum(
                                        fn ($producto) => (float) $producto->precio * (int) $producto->pivot->cantidad
                                    );
                                    $ahorroPromocion = max(0, $totalNormalPromocion - (float) $promocion->precio);
                                @endphp

                                <button
                                    type="button"
                                    wire:key="promocion-venta-{{ $promocion->id }}"
                                    wire:click="agregarPromocion({{ $promocion->id }})"
                                    aria-label="Agregar la promoción {{ $promocion->nombre }} al detalle"
                                    class="group relative flex flex-col rounded-xl border border-amber-200 bg-amber-50/70 p-3 text-left shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 active:translate-y-0 dark:border-amber-700/60 dark:bg-amber-900/20 dark:hover:border-amber-500 dark:focus:ring-offset-stone-900"
                                >
                                    @if ($enCarritoPromocion > 0)
                                        <span class="absolute right-2 top-2 flex size-6 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-stone-900 shadow-sm ring-2 ring-white tabular-nums dark:ring-stone-800" aria-label="{{ $enCarritoPromocion }} en el detalle">
                                            {{ $enCarritoPromocion }}
                                        </span>
                                    @endif

                                    <div class="flex items-start justify-between gap-3 pr-6">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-stone-900 dark:text-stone-50" title="{{ $promocion->nombre }}">
                                                {{ $promocion->nombre }}
                                            </p>
                                            @if (filled($promocion->descripcion))
                                                <p class="mt-0.5 line-clamp-2 text-xs text-stone-500 dark:text-stone-400">
                                                    {{ $promocion->descripcion }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="mt-2 flex flex-wrap items-baseline gap-1.5">
                                        <span class="text-lg font-bold text-amber-700 tabular-nums dark:text-amber-300">Bs {{ number_format((float) $promocion->precio, 2) }}</span>
                                        @if ($totalNormalPromocion > (float) $promocion->precio)
                                            <span class="text-xs font-medium text-stone-400 line-through tabular-nums dark:text-stone-500">Bs {{ number_format($totalNormalPromocion, 2) }}</span>
                                            <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">Ahorras Bs {{ number_format($ahorroPromocion, 2) }}</span>
                                        @endif
                                    </p>

                                    <p class="mt-1 text-xs text-stone-600 dark:text-stone-300">
                                        Incluye:
                                        @foreach ($promocion->productos as $producto)
                                            <span class="inline-block rounded bg-white/80 px-1.5 py-0.5 font-semibold text-stone-800 dark:bg-stone-800/80 dark:text-stone-100">{{ $producto->pivot->cantidad }}× {{ $producto->nombre }}</span>
                                        @endforeach
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($productos->isEmpty() && $promociones->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white px-4 py-12 text-center dark:border-stone-600 dark:bg-stone-800">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500" aria-hidden="true">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-stone-600 dark:text-stone-300">No hay productos para mostrar</p>
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">
                            @if ($categorias->isEmpty())
                                @if ($puedeConfigurar)
                                    Crea la primera categoría y sus productos desde configuración.
                                @else
                                    Avisa al administrador del negocio para que cree las categorías de venta.
                                @endif
                            @else
                                Prueba con otra búsqueda o cambia el filtro de categoría.
                            @endif
                        </p>
                        @if ($puedeConfigurar && $categorias->isEmpty())
                            <a href="{{ route('configuracion.index') }}"
                               class="mt-4 inline-block rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400">
                                Ir a configuración
                            </a>
                        @endif
                    </div>
                @elseif ($productos->isEmpty())
                    <p class="mt-4 rounded-xl border border-dashed border-stone-300 px-4 py-6 text-center text-sm text-stone-500 dark:border-stone-600 dark:text-stone-400">
                        No hay productos individuales para mostrar; puedes vender las promociones de arriba.
                    </p>
                @elseif ($vista === 'grid')
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @foreach ($productos as $producto)
                            @php
                                $enCarrito = $cantidadesPorProducto[$producto->id] ?? 0;
                                $stockBajo = $producto->stock !== null && $producto->stock <= $umbralStockBajo;
                            @endphp

                            <button
                                type="button"
                                wire:key="producto-{{ $producto->id }}"
                                wire:click="agregarProducto({{ $producto->id }})"
                                aria-label="Agregar {{ $producto->nombre }} al detalle"
                                class="group relative flex aspect-square flex-col overflow-hidden rounded-xl border border-stone-200 bg-white text-left shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 active:translate-y-0 dark:border-stone-700 dark:bg-stone-800 dark:hover:border-amber-500/60 dark:focus:ring-offset-stone-900"
                            >
                                <div class="relative min-h-0 flex-1 overflow-hidden bg-stone-100 dark:bg-stone-700">
                                    @if ($producto->imagenUrl())
                                        <img src="{{ $producto->imagenUrl() }}" alt="{{ $producto->nombre }}" class="size-full object-cover transition duration-200 group-hover:scale-105" loading="lazy">
                                    @else
                                        <div class="flex size-full items-center justify-center text-stone-400 dark:text-stone-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                        </div>
                                    @endif

                                    @if ($enCarrito > 0)
                                        <span class="absolute right-1.5 top-1.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-stone-900 shadow-sm ring-2 ring-white tabular-nums dark:ring-stone-800" aria-label="{{ $enCarrito }} en el detalle">
                                            {{ $enCarrito }}
                                        </span>
                                    @endif
                                </div>

                                <div class="shrink-0 px-2 py-1.5">
                                    @if ($producto->categoria?->nombre)
                                        <p class="truncate text-[10px] font-medium uppercase tracking-wide text-stone-400 dark:text-stone-500" title="{{ $producto->categoria->nombre }}">
                                            {{ $producto->categoria->nombre }}
                                        </p>
                                    @endif
                                    <p class="line-clamp-2 break-words text-xs font-bold leading-tight tracking-tight text-stone-900 dark:text-stone-50 sm:text-sm" title="{{ $producto->nombre }}">
                                        {{ $producto->nombre }}
                                    </p>

                                    <div class="mt-0.5 flex items-center justify-between gap-1.5">
                                        <span class="shrink-0 text-sm font-bold text-stone-900 tabular-nums dark:text-stone-100">
                                            Bs {{ number_format((float) $producto->precio, 2) }}
                                        </span>
                                        <span @class([
                                            'truncate text-right text-[10px] font-medium leading-tight',
                                            'text-emerald-600 dark:text-emerald-400' => ! $stockBajo,
                                            'text-red-600 dark:text-red-400' => $stockBajo,
                                        ])>
                                            @if ($producto->stock === null)
                                                Ilimitado
                                            @elseif ($producto->stock <= 0)
                                                Agotado
                                            @elseif ($stockBajo)
                                                Bajo stock
                                            @else
                                                Stock {{ $producto->stock }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <ul class="mt-4 divide-y divide-stone-100 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:divide-stone-700/60 dark:border-stone-700 dark:bg-stone-800" role="list">
                        @foreach ($productos as $producto)
                            @php
                                $enCarrito = $cantidadesPorProducto[$producto->id] ?? 0;
                                $stockBajo = $producto->stock !== null && $producto->stock <= $umbralStockBajo;
                            @endphp

                            <li wire:key="producto-lista-{{ $producto->id }}">
                                <button
                                    type="button"
                                    wire:click="agregarProducto({{ $producto->id }})"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500 dark:hover:bg-stone-700/40"
                                >
                                    <div class="flex min-w-0 flex-1 items-center gap-3">
                                        <div class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                                            @if ($producto->imagenUrl())
                                                <img src="{{ $producto->imagenUrl() }}" alt="{{ $producto->nombre }}" class="size-full object-cover" loading="lazy">
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-stone-900 dark:text-white">
                                                {{ $producto->nombre }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                                {{ $producto->categoria?->nombre }}
                                                <span aria-hidden="true">·</span>
                                                <span @class([
                                                    'font-medium',
                                                    'text-emerald-600 dark:text-emerald-400' => ! $stockBajo,
                                                    'text-red-600 dark:text-red-400' => $stockBajo,
                                                ])>
                                                    @if ($producto->stock === null)
                                                        Stock: ilimitado
                                                    @else
                                                        Stock: {{ $producto->stock }}
                                                    @endif
                                                </span>
                                                @if ($producto->stock !== null && $producto->stock <= 0)
                                                    <span class="ml-1 font-semibold text-red-600 dark:text-red-400">Agotado</span>
                                                @elseif ($stockBajo)
                                                    <span class="ml-1 font-semibold text-red-600 dark:text-red-400">Bajo stock</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-3">
                                        @if ($enCarrito > 0)
                                            <span class="flex size-6 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-stone-900 tabular-nums" aria-label="{{ $enCarrito }} en el detalle">
                                                {{ $enCarrito }}
                                            </span>
                                        @endif
                                        <span class="text-sm font-bold text-stone-900 tabular-nums dark:text-stone-100">
                                            Bs {{ number_format((float) $producto->precio, 2) }}
                                        </span>
                                    </div>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <aside class="mt-6 hidden lg:mt-0 lg:block" aria-label="Detalle de la venta">
                <div class="sticky top-6 flex max-h-[calc(100dvh-10rem)] flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    @include('livewire.ventas._detalle-venta', ['prefijo' => 'escritorio', 'mostrarCerrar' => false])
                </div>
            </aside>
        </div>

        @if ($cantidadItems > 0)
            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-stone-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-stone-700 dark:bg-stone-800/95 lg:hidden">
                <div class="mx-auto flex w-full max-w-2xl items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-stone-500 dark:text-stone-400">
                            {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} en el detalle
                        </p>
                        <p class="text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">Bs {{ $totalCarrito }}</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="carritoAbierto = true"
                        class="shrink-0 rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                    >
                        Ver detalle
                    </button>
                </div>
            </div>
        @endif

        <div x-cloak x-show="carritoAbierto" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Detalle de la venta">
            <div
                x-show="carritoAbierto"
                x-transition.opacity
                class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm"
                x-on:click="carritoAbierto = false"
            ></div>

            <div
                x-show="carritoAbierto"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col overflow-hidden bg-white shadow-xl dark:bg-stone-800"
            >
                @include('livewire.ventas._detalle-venta', ['prefijo' => 'movil', 'mostrarCerrar' => true])
            </div>
        </div>
    @endif

    @if ($mostrarModalPago)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 backdrop-blur-sm sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-pago"
            wire:keydown.escape.window="cerrarModalPago"
        >
            <div class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl border border-stone-200 bg-white shadow-xl dark:border-stone-700 dark:bg-stone-800 sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4 dark:border-stone-700/60">
                    <div class="min-w-0">
                        <h2 id="titulo-pago" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                            Cobrar venta
                        </h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} en el detalle
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="cerrarModalPago"
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-600 hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                        aria-label="Cerrar modal de pago"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center dark:border-amber-700/60 dark:bg-amber-900/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Total a pagar</p>
                        <p class="text-3xl font-bold text-amber-900 tabular-nums dark:text-amber-200">Bs {{ $totalCarrito }}</p>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Tipo de pago</p>

                        <div class="mt-2 grid grid-cols-2 gap-2" role="group" aria-label="Tipo de pago">
                            <button
                                type="button"
                                wire:click="seleccionarMetodoPago('efectivo')"
                                aria-pressed="{{ $metodoPago === 'efectivo' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-xl border px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
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
                                    'rounded-xl border px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
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
                    </div>

                    @if ($metodoPago === 'efectivo')
                        <div class="mt-4">
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
                        @if ($negocio->qrPagoUrl())
                            <div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-3 dark:border-stone-600 dark:bg-stone-900/40">
                                <p class="text-center text-xs text-stone-600 dark:text-stone-300">
                                    Escanea el QR para pagar <strong class="font-semibold">Bs {{ $totalCarrito }}</strong>.
                                </p>

                                <button
                                    type="button"
                                    x-on:click="qrAmpliado = true"
                                    class="mx-auto mt-3 block rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    aria-label="Ampliar QR de pago"
                                >
                                    <img
                                        src="{{ $negocio->qrPagoUrl() }}"
                                        alt="QR de pago de {{ $negocio->nombre_comercial }}"
                                        class="mx-auto h-48 w-48 rounded-lg bg-white object-contain p-2 ring-1 ring-stone-200 dark:ring-stone-600"
                                    >
                                </button>

                                <p class="mt-2 text-center text-[11px] text-stone-400 dark:text-stone-500">
                                    Toca la imagen para ampliarla.
                                </p>
                            </div>
                        @else
                            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-300" role="status">
                                Este negocio no tiene un QR configurado. Puedes registrar el cobro igualmente o pedir al administrador que lo agregue en Configuración.
                            </p>
                        @endif
                    @else
                        <p class="mt-4 text-xs text-stone-400 dark:text-stone-500">
                            Selecciona cómo pagará el cliente para continuar.
                        </p>
                    @endif

                    <div class="mt-4">
                        <label
                            for="paraLlevar"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-300 bg-white p-3 transition hover:border-amber-400 hover:bg-amber-50/50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:border-stone-600 dark:bg-stone-900/40 dark:hover:border-amber-500/60 dark:hover:bg-amber-900/10 dark:has-[:checked]:border-emerald-500/70 dark:has-[:checked]:bg-emerald-900/20"
                        >
                            <input
                                type="checkbox"
                                id="paraLlevar"
                                wire:model.live="paraLlevar"
                                class="peer sr-only"
                            >
                            <span
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-md border-2 border-stone-300 bg-white text-white transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2 [&>svg]:opacity-0 peer-checked:[&>svg]:opacity-100 dark:border-stone-500 dark:bg-stone-800 dark:peer-checked:border-emerald-500 dark:peer-checked:bg-emerald-500 dark:peer-focus-visible:ring-offset-stone-800"
                                aria-hidden="true"
                            >
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m5 12 5 5L20 7" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-stone-900 dark:text-stone-100">Para llevar</span>
                                <span class="mt-0.5 block text-xs text-stone-500 dark:text-stone-400">
                                    La comida no se consumirá dentro del local.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label for="glosa" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            Glosa <span class="font-normal normal-case">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            id="glosa"
                            wire:model="glosa"
                            maxlength="255"
                            placeholder="Ej.: para llevar, sin cebolla…"
                            class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:placeholder:text-stone-500"
                        >

                        @error('glosa')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <label for="cliente" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            Cliente <span class="font-normal normal-case">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            id="cliente"
                            wire:model="cliente"
                            maxlength="120"
                            placeholder="Ej.: Juan Pérez"
                            class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:placeholder:text-stone-500"
                        >

                        @error('cliente')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-stone-100 px-5 py-4 dark:border-stone-700/60">
                    @error('carrito')
                        <p class="mb-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="cerrarModalPago"
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                        >
                            Cancelar
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
            </div>
        </div>

        <div
            x-cloak
            x-show="qrAmpliado"
            x-transition.opacity
            class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-900/80 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="QR de pago ampliado"
            x-on:click.self="qrAmpliado = false"
        >
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-4 shadow-xl dark:bg-stone-800">
                <button
                    type="button"
                    x-on:click="qrAmpliado = false"
                    class="absolute right-2 top-2 flex size-9 items-center justify-center rounded-full bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                    aria-label="Cerrar QR ampliado"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>

                @if ($negocio !== null && $negocio->qrPagoUrl())
                    <img
                        src="{{ $negocio->qrPagoUrl() }}"
                        alt="QR de pago ampliado de {{ $negocio->nombre_comercial }}"
                        class="mx-auto max-h-[70vh] w-full rounded-lg bg-white object-contain p-2"
                    >

                    <p class="mt-2 text-center text-sm font-semibold text-stone-800 dark:text-stone-100">
                        Paga Bs {{ $totalCarrito }} escaneando este QR
                    </p>
                @endif
            </div>
        </div>
    @endif
</div>
