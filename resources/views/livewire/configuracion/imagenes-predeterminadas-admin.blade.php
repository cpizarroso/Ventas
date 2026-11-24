<div>
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
                    Sube una imagen maestra de al menos 800 × 800 px. El sistema genera las versiones de 256, 512 y 800 px automáticamente.
                </p>
            </div>
            <button type="button"
                    wire:click="abrirNueva"
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
                                        class="rounded-lg bg-white/90 px-2 py-1 text-xs font-semibold text-stone-800 hover:bg-white"
                                        aria-label="Editar {{ $imagen['archivo'] }}">
                                    Editar
                                </button>
                                <button type="button"
                                        wire:click="confirmarEliminacion('{{ $imagen['id'] }}')"
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
        <form wire:submit="guardar" class="space-y-4">
            @php
                $campo = $editandoId === null ? 'imagenNueva' : 'imagenEdicion';
                $archivo = $editandoId === null ? $imagenNueva : $imagenEdicion;
            @endphp

            <div class="flex items-center gap-3">
                <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                    @if ($archivo && $archivo->isPreviewable())
                        <img src="{{ $archivo->temporaryUrl() }}" alt="Vista previa de la imagen" class="size-full object-cover">
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 1 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375 0 1 0-.75 0 .375.375 0 0 0 .75 0Z" />
                        </svg>
                    @endif
                </div>
                <div class="min-w-0">
                    <label for="imagen-predeterminada-archivo"
                           class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                        Elegir imagen
                    </label>
                    <input id="imagen-predeterminada-archivo"
                           type="file"
                           wire:model="{{ $campo }}"
                           accept="image/jpeg,image/png,image/webp"
                           class="sr-only">
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">JPG, PNG o WebP · máx. 4 MB · mínimo 800 × 800 px</p>
                </div>
            </div>

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
                        class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                    Cancelar
                </button>
                <button type="submit"
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
                    class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                Cancelar
            </button>
            <button type="button"
                    wire:click="eliminar"
                    class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                Eliminar
            </button>
        </div>
    </x-modal-configuracion>
</div>
