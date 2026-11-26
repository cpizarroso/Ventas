@php
    $diasEtiquetas = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
    $totalNormal = collect($items)->sum(function (array $item) use ($productos): float {
        $producto = $productos->firstWhere('id', (int) ($item['producto_id'] ?? 0));

        return $producto === null
            ? 0.0
            : (float) $producto->precio * (int) ($item['cantidad'] ?? 0);
    });
@endphp

<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Promociones</h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                Combina varios ítems con un precio único, agrega una imagen y define cuándo estará disponible.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    wire:click="abrirModal"
                    class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-amber-400">
                Nueva promoción
            </button>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                {{ $promociones->total() }} {{ $promociones->total() === 1 ? 'promoción' : 'promociones' }}
            </span>
        </div>
    </div>

    <div class="mt-5 max-w-sm">
        <label for="buscar-promociones" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
            Buscar promoción
        </label>
        <input id="buscar-promociones"
               type="search"
               wire:model.live.debounce.300ms="buscar"
               placeholder="Nombre, descripción o ítem"
               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
    </div>

    <x-modal-configuracion id="modal-promocion"
                           :titulo="$editandoId === null ? 'Nueva promoción' : 'Editar promoción'"
                           descripcion="Define el precio, el stock, la vigencia y los ítems incluidos."
                           cerrar="cerrarModal"
                           ancho="max-w-4xl"
                           :abierto="$modalAbierto">
        <form wire:submit="guardar" class="space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="promocion-nombre" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Nombre</label>
                    <input id="promocion-nombre"
                           type="text"
                           wire:model="nombre"
                           placeholder="Ej. Combo desayuno"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('nombre')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="promocion-descripcion" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Descripción <span class="font-normal normal-case">(opcional)</span></label>
                    <input id="promocion-descripcion"
                           type="text"
                           wire:model="descripcion"
                           placeholder="Ej. Café + porción de torta"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('descripcion')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Imagen de la promoción <span class="font-normal normal-case">(opcional)</span></p>
                    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-stone-200 bg-stone-50 p-3 dark:border-stone-700 dark:bg-stone-900/40">
                        <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-100 text-stone-400 ring-1 ring-stone-200 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600">
                            @if ($quitarImagen)
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3" />
                                </svg>
                            @elseif ($promocionImagen && $promocionImagen->isPreviewable())
                                <img src="{{ $promocionImagen->temporaryUrl() }}" alt="Vista previa de la imagen seleccionada" class="size-full object-cover">
                            @elseif ($promocionEditando?->imagenUrl())
                                <img src="{{ $promocionEditando->imagenUrl() }}" alt="Imagen actual de la promoción" class="size-full object-cover">
                            @else
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="3" width="18" height="18" rx="3" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <path d="m21 15-5-5L5 21" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <label class="inline-flex cursor-pointer rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                Elegir imagen
                                <input type="file" wire:model="promocionImagen" accept="image/jpeg,image/png,image/webp" class="sr-only">
                            </label>
                            @if ($editandoId !== null && $promocionEditando?->imagen)
                                <button type="button"
                                        wire:click="alternarQuitarImagen"
                                        class="ml-2 rounded-lg px-2 py-1.5 text-xs font-semibold {{ $quitarImagen ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' : 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30' }}">
                                    {{ $quitarImagen ? 'Restaurar' : 'Quitar' }}
                                </button>
                            @endif
                            <p class="mt-1 text-[11px] text-stone-400 dark:text-stone-500">JPG, PNG o WebP. Máx. 2 MB.</p>
                            @if ($quitarImagen)
                                <p class="mt-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300">La imagen actual se quitará al guardar.</p>
                            @endif
                        </div>
                    </div>
                    @error('promocionImagen')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="promocion-precio" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Precio promocional</label>
                    <input id="promocion-precio"
                           type="text"
                           inputmode="decimal"
                           wire:model="precio"
                           placeholder="0.00"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('precio')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between gap-2">
                        <label for="promocion-stock" class="block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Stock de la promoción</label>
                        <button type="button"
                                wire:click="alternarStockIlimitado"
                                class="rounded-lg bg-stone-100 px-2 py-1 text-[11px] font-semibold text-stone-600 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600">
                            {{ $promocionStockIlimitado ? 'Ilimitado' : 'Con stock' }}
                        </button>
                    </div>
                    @unless ($promocionStockIlimitado)
                        <input id="promocion-stock"
                               type="number"
                               min="0"
                               max="999999"
                               wire:model="promocionStock"
                               placeholder="0"
                               class="mt-1 w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @endunless
                    @error('promocionStock')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-[11px] leading-tight text-stone-400 dark:text-stone-500">
                        Al llegar a cero se avisará, pero la venta no se bloqueará.
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        Total normal de los ítems:
                        <span class="font-semibold text-stone-800 tabular-nums dark:text-stone-200">Bs {{ number_format($totalNormal, 2) }}</span>
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <x-calendario-rango
                        id="promocion-vigencia"
                        etiqueta="Vigencia de la promoción"
                        nombre-desde="fechaInicio"
                        nombre-hasta="fechaFin"
                        :desde="$fechaInicio"
                        :hasta="$fechaFin"
                        :auto-enviar="false"
                        wire-model-desde="fechaInicio"
                        wire-model-hasta="fechaFin"
                    />
                    @error('fechaInicio')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                    @error('fechaFin')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <fieldset>
                <legend class="mb-2 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Días disponibles</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($diasEtiquetas as $dia => $etiqueta)
                        <label for="promocion-dia-{{ $dia }}" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:hover:bg-stone-700">
                            <input id="promocion-dia-{{ $dia }}"
                                   type="checkbox"
                                   value="{{ $dia }}"
                                   wire:model="diasSemana"
                                   class="size-3.5 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                            {{ $etiqueta }}
                        </label>
                    @endforeach
                </div>
                @error('diasSemana')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </fieldset>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ítems del combo</p>
                    <button type="button"
                            wire:click="agregarItem"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/40">
                        + Agregar ítem
                    </button>
                </div>

                @if ($productos->isEmpty())
                    <p class="mt-2 rounded-xl border border-dashed border-stone-300 px-3 py-4 text-xs text-stone-500 dark:border-stone-600 dark:text-stone-400">
                        Primero crea ítems en las categorías de venta para poder armar una promoción.
                    </p>
                @endif

                <div class="mt-2 space-y-2">
                    @foreach ($items as $indice => $item)
                        <div wire:key="promocion-item-{{ $indice }}" class="grid gap-2 rounded-xl border border-stone-200 bg-stone-50 p-3 dark:border-stone-700 dark:bg-stone-900/40 sm:grid-cols-[1fr_7rem_auto] sm:items-start">
                            <div>
                                <label for="promocion-item-{{ $indice }}" class="sr-only">Ítem {{ $indice + 1 }}</label>
                                <select id="promocion-item-{{ $indice }}"
                                        wire:model="items.{{ $indice }}.producto_id"
                                        class="selector-tema">
                                    <option value="">Selecciona un ítem</option>
                                    @foreach ($productos->groupBy(fn ($producto) => $producto->categoria?->nombre ?? 'Sin categoría') as $categoria => $grupo)
                                        <optgroup label="{{ $categoria }}">
                                            @foreach ($grupo as $producto)
                                                <option value="{{ $producto->id }}">
                                                    {{ $producto->nombre }} — Bs {{ number_format((float) $producto->precio, 2) }}{{ $producto->categoria?->activa === false ? ' (categoría inactiva)' : '' }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error("items.{$indice}.producto_id")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="promocion-cantidad-{{ $indice }}" class="sr-only">Cantidad</label>
                                <input id="promocion-cantidad-{{ $indice }}"
                                       type="number"
                                       min="1"
                                       max="999"
                                       wire:model="items.{{ $indice }}.cantidad"
                                       class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                                @error("items.{$indice}.cantidad")
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="button"
                                    wire:click="quitarItem({{ $indice }})"
                                    class="rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/40">
                                Quitar
                            </button>
                        </div>
                    @endforeach
                </div>
                @error('items')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-4 dark:border-stone-700/60">
                <label for="promocion-activa" class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-stone-700 dark:text-stone-200">
                    <input id="promocion-activa"
                           type="checkbox"
                           wire:model="activa"
                           class="size-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                    Promoción activa
                </label>

                <button type="submit"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-stone-800"
                        wire:loading.attr="disabled">
                    {{ $editandoId === null ? 'Crear promoción' : 'Guardar cambios' }}
                </button>
            </div>
        </form>
    </x-modal-configuracion>

    <div class="mt-8 border-t border-stone-100 pt-6 dark:border-stone-700/60">
        <h3 class="text-sm font-semibold text-stone-900 dark:text-stone-100">Promociones creadas</h3>
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
            Dar de baja una promoción la oculta de las ventas sin eliminarla; puedes reactivarla cuando quieras.
        </p>

        @if ($promociones->isEmpty())
            <div class="mt-3 rounded-xl border border-dashed border-stone-300 px-4 py-8 text-center dark:border-stone-600">
                <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Todavía no hay promociones</p>
                <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Crea la primera con el botón «Nueva promoción».</p>
            </div>
        @else
            <ul class="mt-3 divide-y divide-stone-100 dark:divide-stone-700/60" role="list">
                @foreach ($promociones as $promocion)
                    @php
                        $estado = $this->estado($promocion);
                        $totalNormalPromocion = $promocion->productos->sum(
                            fn ($producto) => (float) $producto->precio * (int) $producto->pivot->cantidad
                        );
                    @endphp

                    <li wire:key="promocion-{{ $promocion->id }}" class="py-4">
                        <div class="flex flex-wrap items-start gap-3">
                            @if ($promocion->imagenUrl())
                                <button type="button"
                                        wire:click="abrirImagenPromocion({{ $promocion->id }})"
                                        title="Ampliar imagen de {{ $promocion->nombre }}"
                                        aria-label="Ampliar imagen de {{ $promocion->nombre }}"
                                        class="size-14 shrink-0 overflow-hidden rounded-lg bg-stone-100 ring-1 ring-stone-200 transition hover:ring-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-stone-700 dark:ring-stone-600">
                                    <img src="{{ $promocion->imagenUrl() }}" alt="" class="size-full object-cover">
                                </button>
                            @else
                                <div class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-stone-400 ring-1 ring-stone-200 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600" aria-hidden="true">
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="18" height="18" rx="3" />
                                        <circle cx="8.5" cy="8.5" r="1.5" />
                                        <path d="m21 15-5-5L5 21" />
                                    </svg>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $promocion->nombre }}</h4>
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estado['clases'] }}">{{ $estado['texto'] }}</span>
                                    @if ($promocion->stock === null)
                                        <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">Stock ilimitado</span>
                                    @elseif ($promocion->sinStock())
                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">Sin stock ({{ $promocion->stock }})</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Stock: {{ $promocion->stock }}</span>
                                    @endif
                                </div>
                                @if (filled($promocion->descripcion))
                                    <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">{{ $promocion->descripcion }}</p>
                                @endif
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                    Bs {{ number_format((float) $promocion->precio, 2) }}
                                    <span class="text-stone-400 dark:text-stone-500">·</span>
                                    Del {{ $promocion->fecha_inicio->format('d/m/Y') }} al {{ $promocion->fecha_fin->format('d/m/Y') }}
                                    <span class="text-stone-400 dark:text-stone-500">·</span>
                                    Total normal: Bs {{ number_format($totalNormalPromocion, 2) }}
                                </p>
                                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                                    Días:
                                    {{ collect($promocion->dias_semana ?? [])->map(fn ($dia) => $diasEtiquetas[(int) $dia] ?? '')->filter()->implode(', ') }}
                                </p>
                                <p class="mt-1 text-xs text-stone-600 dark:text-stone-300">
                                    Incluye:
                                    @forelse ($promocion->productos as $producto)
                                        <span class="inline-block rounded bg-stone-100 px-1.5 py-0.5 dark:bg-stone-700">{{ $producto->pivot->cantidad }}× {{ $producto->nombre }}</span>
                                    @empty
                                        <span class="text-stone-400 dark:text-stone-500">Sin ítems</span>
                                    @endforelse
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap gap-1">
                                <button type="button"
                                        wire:click="editar({{ $promocion->id }})"
                                        data-preservar-scroll
                                        title="Editar {{ $promocion->nombre }}"
                                        aria-label="Editar {{ $promocion->nombre }}"
                                        @if ($modalAbierto) disabled @endif
                                        class="rounded-lg bg-sky-50 p-1.5 text-sky-700 shadow-sm ring-1 ring-sky-200 transition-all hover:scale-105 hover:bg-sky-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50 dark:focus-visible:ring-offset-stone-800">
                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                    </svg>
                                </button>
                                @if ($promocion->activa)
                                    <button type="button"
                                            wire:click="cambiarActiva({{ $promocion->id }})"
                                            data-preservar-scroll
                                            title="Dar de baja a {{ $promocion->nombre }}"
                                            aria-label="Dar de baja a {{ $promocion->nombre }}"
                                            @if ($modalAbierto) disabled @endif
                                            class="rounded-lg bg-red-50 p-1.5 text-red-700 shadow-sm ring-1 ring-red-200 transition-all hover:scale-105 hover:bg-red-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-900/50 dark:focus-visible:ring-offset-stone-800">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                @else
                                    <button type="button"
                                            wire:click="cambiarActiva({{ $promocion->id }})"
                                            data-preservar-scroll
                                            title="Reactivar {{ $promocion->nombre }}"
                                            aria-label="Reactivar {{ $promocion->nombre }}"
                                            @if ($modalAbierto) disabled @endif
                                            class="rounded-lg bg-emerald-50 p-1.5 text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition-all hover:scale-105 hover:bg-emerald-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/50 dark:focus-visible:ring-offset-stone-800">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{ $promociones->links('components.paginacion-compacta', ['enLivewire' => true]) }}
        @endif
    </div>

    <x-modal-configuracion id="modal-imagen-promocion"
                           :titulo="'Imagen de '.$imagenAmpliadaNombre"
                           descripcion="Vista ampliada de la imagen de la promoción."
                           cerrar="cerrarImagenAmpliada"
                           ancho="max-w-3xl"
                           :abierto="$imagenAmpliadaUrl !== null">
        @if ($imagenAmpliadaUrl)
            <img src="{{ $imagenAmpliadaUrl }}"
                 alt="Imagen ampliada de {{ $imagenAmpliadaNombre }}"
                 class="mx-auto max-h-[70vh] w-auto max-w-full rounded-xl object-contain">
        @endif
    </x-modal-configuracion>
</div>
