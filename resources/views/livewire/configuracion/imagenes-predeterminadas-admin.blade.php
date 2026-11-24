<div id="area-imagenes-predeterminadas">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Imágenes predeterminadas</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Elige una imagen, selecciona el área cuadrada y el sistema genera las versiones de 256, 512 y 800 px automáticamente.
                </p>
            </div>
            <button type="button"
                    wire:click="abrirNueva"
                    data-preservar-scroll
                    class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-amber-400">
                Agregar imagen
            </button>
        </div>

        <div class="mt-5 flex flex-wrap items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Vista</span>
            <div class="inline-flex rounded-xl border border-stone-200 bg-stone-50 p-1 dark:border-stone-700 dark:bg-stone-900" role="group" aria-label="Tamaño de vista">
                @foreach ($tamanos as $tamano)
                    <button type="button"
                            wire:click="$set('tamanoVista', {{ $tamano }})"
                            data-preservar-scroll
                            @class([
                                'rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                'bg-amber-500 text-stone-900 shadow-sm' => $tamanoVista === $tamano,
                                'text-stone-600 hover:bg-white hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-800 dark:hover:text-white' => $tamanoVista !== $tamano,
                            ])
                            aria-pressed="{{ $tamanoVista === $tamano ? 'true' : 'false' }}">
                        {{ $tamano }} × {{ $tamano }}
                    </button>
                @endforeach
            </div>
            <span class="text-xs text-stone-500 dark:text-stone-400">
                {{ count($imagenes) }} {{ count($imagenes) === 1 ? 'imagen' : 'imágenes' }}
            </span>
        </div>

        @if ($imagenes === [])
            <div class="mt-5 rounded-xl border border-dashed border-stone-300 px-4 py-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:text-stone-400">
                Aún no hay imágenes predeterminadas.
            </div>
        @else
            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6">
                @foreach ($imagenes as $imagen)
                    <div wire:key="predeterminada-{{ $imagen['id'] }}"
                         class="group overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-900">
                        <div class="relative flex aspect-square items-center justify-center overflow-hidden bg-stone-100 dark:bg-stone-700">
                            <img src="{{ $imagen['url'] }}"
                                 alt="{{ $imagen['archivo'] }}"
                                 class="size-full object-contain"
                                 loading="lazy">
                            <div class="absolute inset-x-0 bottom-0 flex justify-center gap-1 bg-stone-900/60 px-1 py-1 opacity-0 transition group-hover:opacity-100">
                                <button type="button"
                                        wire:click="abrirEdicion('{{ $imagen['id'] }}')"
                                        data-preservar-scroll
                                        class="rounded-lg bg-white/90 px-2 py-1 text-xs font-semibold text-stone-800 hover:bg-white"
                                        aria-label="Editar {{ $imagen['archivo'] }}">
                                    Editar
                                </button>
                                <button type="button"
                                        wire:click="confirmarEliminacion('{{ $imagen['id'] }}')"
                                        data-preservar-scroll
                                        class="rounded-lg bg-red-600/90 px-2 py-1 text-xs font-semibold text-white hover:bg-red-600"
                                        aria-label="Eliminar {{ $imagen['archivo'] }}">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                        <div class="px-2 py-2">
                            <p class="truncate text-xs font-semibold text-stone-800 dark:text-stone-100" title="{{ $imagen['archivo'] }}">
                                {{ $imagen['archivo'] }}
                            </p>
                            <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                <span class="rounded-full bg-stone-100 px-1.5 py-0.5 text-[10px] font-medium text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                                    {{ $imagen['ancho'] ?? '?' }} × {{ $imagen['alto'] ?? '?' }}
                                </span>
                                @if ($imagen['legacy'])
                                    <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                        Legacy
                                    </span>
                                @elseif ($imagen['real'] !== $tamanoVista)
                                    <span class="rounded-full bg-stone-100 px-1.5 py-0.5 text-[10px] font-medium text-stone-500 dark:bg-stone-700 dark:text-stone-400">
                                        {{ $imagen['real'] }} px
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <x-modal-configuracion id="modal-imagen-predeterminada"
                           titulo="{{ $editandoId === null ? 'Agregar imagen predeterminada' : 'Editar imagen predeterminada' }}"
                           descripcion="La imagen se recortará a cuadrado y se generarán las tres versiones automáticamente."
                           cerrar="cerrarModal"
                           :abierto="$modalAbierto"
                           ancho="max-w-lg">
        <form x-on:submit.prevent="window.guardarRecorte($wire)" class="space-y-4">
            @php
                $campo = $editandoId === null ? 'imagenNueva' : 'imagenEdicion';
                $archivo = $editandoId === null ? $imagenNueva : $imagenEdicion;
            @endphp

            <div>
                <label for="imagen-predeterminada-archivo"
                       class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                    Elegir imagen
                </label>
                <input id="imagen-predeterminada-archivo"
                       type="file"
                       wire:model="{{ $campo }}"
                       x-on:change="window.prepararRecorte()"
                       accept="image/jpeg,image/png,image/webp"
                       class="sr-only">
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    JPG, PNG o WebP · máx. 4 MB · arrastra el recuadro para elegir la parte cuadrada.
                </p>
            </div>

            <div class="relative h-64 w-full overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-700">
                <img id="imagen-recorte"
                     src="{{ $archivo && $archivo->isPreviewable() ? $archivo->temporaryUrl() : '' }}"
                     alt="Editor de recorte cuadrado"
                     class="block size-full object-contain"
                     @style(['display: none' => ! $archivo])>
                @if (! $archivo)
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-2 text-stone-400 dark:text-stone-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-4m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-xs">Elige una imagen para recortarla</span>
                    </div>
                @endif
            </div>

            @if ($archivo)
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">Zoom</span>
                    <input type="range" min="1" max="3" step="0.05" value="1"
                           x-on:input="window.ajustarZoomRecorte($event.target.value)"
                           class="h-1 flex-1 accent-amber-500"
                           aria-label="Zoom del recorte">
                    <button type="button"
                            x-on:click="window.reiniciarRecorte()"
                            class="rounded-lg border border-stone-300 px-2.5 py-1 text-xs font-semibold text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700">
                        Restablecer
                    </button>
                </div>
                <p id="aviso-recorte-pequeno" class="hidden rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    El área elegida es menor a 800 px; se ampliará y puede perder nitidez.
                </p>
            @endif

            @error($campo)
                <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            @if ($editandoId !== null)
                <p class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                    Reemplazar esta imagen creará una nueva versión optimizada. La anterior se eliminará.
                </p>
            @endif

            <div class="flex flex-wrap justify-end gap-2">
                <button type="button"
                        wire:click="cerrarModal"
                        data-preservar-scroll
                        class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                    Cancelar
                </button>
                <button type="submit"
                        data-preservar-scroll
                        data-recargar-scroll
                        class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                    Guardar
                </button>
            </div>
        </form>
    </x-modal-configuracion>

    <x-modal-configuracion id="modal-eliminar-imagen"
                           titulo="Eliminar imagen predeterminada"
                           descripcion="Esta acción no se puede deshacer."
                           cerrar="cancelarEliminacion"
                           :abierto="$eliminandoId !== null"
                           ancho="max-w-md">
        <p class="text-sm text-stone-600 dark:text-stone-300">
            ¿Eliminar <strong class="font-semibold">{{ $eliminandoId }}</strong> y sus versiones de 256, 512 y 800 px?
        </p>
        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button"
                    wire:click="cancelarEliminacion"
                    data-preservar-scroll
                    class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                Cancelar
            </button>
            <button type="button"
                    wire:click="eliminar"
                    data-preservar-scroll
                    data-recargar-scroll
                    class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                Eliminar
            </button>
        </div>
    </x-modal-configuracion>
</div>
