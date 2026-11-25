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
                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-3 xl:grid-cols-5">
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
                                class="group relative flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white text-left shadow-sm transition duration-150 hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 active:translate-y-0 dark:border-stone-700 dark:bg-stone-800 dark:hover:border-amber-500/60 dark:focus:ring-offset-stone-900"
                            >
                                <div class="relative aspect-square shrink-0 overflow-hidden bg-stone-100 dark:bg-stone-700">
                                    @if ($producto->imagenUrl())
                                        <img src="{{ $producto->imagenUrl() }}" alt="{{ $producto->nombre }}" class="size-full object-contain transition duration-200 group-hover:scale-105" loading="lazy" decoding="async">
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

                                <div class="shrink-0 px-2 py-2">
                                    @if ($producto->categoria?->nombre)
                                        <p class="truncate text-[10px] font-medium uppercase tracking-wide text-stone-400 dark:text-stone-500" title="{{ $producto->categoria->nombre }}">
                                            {{ $producto->categoria->nombre }}
                                        </p>
                                    @endif
                                    <p class="line-clamp-2 break-words text-xs font-bold leading-tight tracking-tight text-stone-900 dark:text-stone-50" title="{{ $producto->nombre }}">
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
                                                <img src="{{ $producto->imagenUrl() }}" alt="{{ $producto->nombre }}" class="size-full object-cover" loading="lazy" decoding="async">
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
