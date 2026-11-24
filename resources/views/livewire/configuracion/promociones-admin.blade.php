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

    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Promociones</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Combina varios ítems con un precio único y define cuándo estará disponible.
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
                               descripcion="Define el precio, la vigencia y los ítems incluidos."
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

                <div class="flex items-end">
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
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $promocion->nombre }}</h4>
                                        <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estado['clases'] }}">{{ $estado['texto'] }}</span>
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
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                                        Editar
                                    </button>
                                    <button type="button"
                                            wire:click="confirmarEliminacion({{ $promocion->id }})"
                                            class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/40">
                                        Eliminar
                                    </button>
                                </div>
                            </div>

                            @if ($eliminandoId === $promocion->id)
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900 dark:bg-red-900/30">
                                    <p class="text-sm font-medium text-red-800 dark:text-red-300">
                                        ¿Eliminar «{{ $promocion->nombre }}»? Las ventas históricas se conservarán.
                                    </p>
                                    <div class="flex shrink-0 gap-2">
                                        <button type="button"
                                                wire:click="eliminar"
                                                class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500">
                                            Sí, eliminar
                                        </button>
                                        <button type="button"
                                                wire:click="cancelarEliminacion"
                                                class="rounded-lg bg-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                {{ $promociones->links('components.paginacion-compacta', ['enLivewire' => true]) }}
            @endif
        </div>
    </div>
</div>
