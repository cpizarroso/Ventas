<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Categorías de venta</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Crea y administra las categorías que aparecerán en la pantalla principal de ventas.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        wire:click="abrirModal"
                        class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-amber-400">
                    Nueva categoría
                </button>
                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                    {{ $categorias->total() }} {{ $categorias->total() === 1 ? 'categoría' : 'categorías' }}
                </span>
            </div>
        </div>

        <div class="mt-5 max-w-sm">
            <label for="buscar-categorias" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                Buscar categoría
            </label>
            <input id="buscar-categorias"
                   type="search"
                   wire:model.live.debounce.300ms="buscar"
                   placeholder="Nombre de la categoría"
                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
        </div>

        <x-modal-configuracion id="modal-categoria"
                               :titulo="$editandoId === null ? 'Nueva categoría' : 'Editar categoría'"
                               descripcion="El nombre debe ser único dentro de tu negocio."
                               cerrar="solicitarCierreCategoria"
                               :abierto="$modalAbierto"
                               :escape="$confirmandoDescartar === null">
            <form wire:submit="crear" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <label for="nombre" class="sr-only">Nombre de la categoría</label>
                    <input id="nombre"
                           type="text"
                           wire:model="nombre"
                           placeholder="Nombre de la nueva categoría"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('nombre')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                    Agregar categoría
                </button>
            </form>

            @if ($editandoId !== null)
                <form wire:submit="guardarEdicion" class="mt-5 border-t border-stone-100 pt-5 dark:border-stone-700/60">
                    <label for="categoria-nombre-edicion" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Nuevo nombre
                    </label>
                    <input id="categoria-nombre-edicion"
                           type="text"
                           wire:model="nombreEdicion"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('nombreEdicion')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button"
                                wire:click="solicitarCierreCategoria"
                                class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                            Guardar
                        </button>
                    </div>
                </form>
            @endif
        </x-modal-configuracion>

        <x-modal-configuracion id="modal-producto"
                               titulo="Agregar ítem"
                               descripcion="El ítem se creará en la categoría abierta. El nombre debe ser único dentro de la categoría."
                               cerrar="solicitarCierreProducto"
                               :abierto="$modalProductoAbierto"
                               :escape="$confirmandoDescartar === null && ! $mostrarGaleriaPredeterminada"
                               ancho="max-w-xl">
            <form wire:submit="crearProducto" class="space-y-4">
                @php
                    $predeterminadaSeleccionada = $productoImagenPredeterminada !== null
                        ? collect($imagenesPredeterminadas)->firstWhere('archivo', $productoImagenPredeterminada)
                        : null;
                @endphp

                <div class="flex items-center gap-3">
                    <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-stone-200 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                        @if ($productoImagen && $productoImagen->isPreviewable())
                            <img src="{{ $productoImagen->temporaryUrl() }}" alt="Vista previa de la imagen" class="size-full object-cover">
                        @elseif ($predeterminadaSeleccionada)
                            <img src="{{ $predeterminadaSeleccionada['url'] }}" alt="Imagen predeterminada seleccionada" class="size-full object-cover">
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <label for="producto-imagen"
                                   class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                Elegir imagen
                            </label>
                            <input id="producto-imagen"
                                   type="file"
                                   wire:model="productoImagen"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="sr-only">
                            @if (! empty($imagenesPredeterminadas))
                                <button type="button"
                                        wire:click="abrirGaleriaPredeterminada"
                                        class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                    Imágenes predeterminadas
                                </button>
                            @endif
                            @if ($productoImagenPredeterminada !== null)
                                <button type="button"
                                        wire:click="quitarImagenPredeterminada"
                                        class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                    Quitar predeterminada
                                </button>
                            @endif
                        </div>
                        <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">
                            Imagen cuadrada ideal 800×800 px. JPG, PNG o WebP. Máx. 2 MB. Se optimiza automáticamente.
                        </p>
                        @if ($productoImagen)
                            <label class="mt-2 inline-flex cursor-pointer items-center gap-1.5 text-[11px] font-medium text-stone-600 dark:text-stone-300">
                                <input type="checkbox"
                                       wire:model="guardarEnPredeterminadas"
                                       class="rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                                Guardar esta imagen en imágenes predeterminadas
                            </label>
                        @endif
                        <p wire:loading wire:target="productoImagen" class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                            Subiendo imagen…
                        </p>
                        @error('productoImagen')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                        @error('productoImagenPredeterminada')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="producto-nombre" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                        Nombre del ítem <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="producto-nombre"
                           type="text"
                           wire:model="productoNombre"
                           placeholder="Ej: Café con leche"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('productoNombre')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label for="producto-precio" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                            Precio <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input id="producto-precio"
                               type="number"
                               step="0.01"
                               min="0"
                               wire:model="productoPrecio"
                               placeholder="0.00"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                        @error('productoPrecio')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="producto-stock" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                            Stock <span class="text-xs font-normal text-stone-400">(opcional)</span>
                        </label>
                        <input id="producto-stock"
                               type="number"
                               step="1"
                               min="0"
                               wire:model="productoStock"
                               placeholder="Vacío = ilimitado"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                        @error('productoStock')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-stone-100 pt-4 dark:border-stone-700/60">
                    <button type="button"
                            wire:click="solicitarCierreProducto"
                            class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-amber-500 px-5 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                        Agregar ítem
                    </button>
                </div>
            </form>
        </x-modal-configuracion>

        <x-modal-configuracion id="modal-editar-producto"
                               titulo="Editar ítem"
                               descripcion="Actualiza el nombre, el precio, el stock o la imagen del ítem."
                               cerrar="solicitarCierreEdicionProducto"
                               :abierto="$productoEditandoId !== null"
                               :escape="$confirmandoDescartar === null && ! $mostrarGaleriaPredeterminadaEdicion"
                               ancho="max-w-xl">
            @php
                $producto = $this->productoEditando();
            @endphp

            @if ($producto !== null)
                <form wire:submit="guardarEdicionProducto" class="space-y-4">
                    @php
                        $predeterminadaEdicionSeleccionada = $productoImagenPredeterminadaEdicion !== null
                            ? collect($imagenesPredeterminadas)->firstWhere('archivo', $productoImagenPredeterminadaEdicion)
                            : null;
                    @endphp

                    <div class="flex items-center gap-3">
                        <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-stone-200 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                            @if ($productoImagenEdicion && $productoImagenEdicion->isPreviewable())
                                <img src="{{ $productoImagenEdicion->temporaryUrl() }}" alt="Vista previa de la imagen" class="size-full object-cover">
                            @elseif (! $quitarImagenEdicion && $predeterminadaEdicionSeleccionada)
                                <img src="{{ $predeterminadaEdicionSeleccionada['url'] }}" alt="Imagen predeterminada seleccionada" class="size-full object-cover">
                            @elseif (! $quitarImagenEdicion && $producto->imagenUrl())
                                <img src="{{ $producto->imagenUrl() }}" alt="{{ $producto->nombre }}" class="size-full object-cover">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <label for="producto-edicion-imagen"
                                       class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                    Cambiar imagen
                                </label>
                                <input id="producto-edicion-imagen"
                                       type="file"
                                       wire:model="productoImagenEdicion"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="sr-only">
                                @if (! empty($imagenesPredeterminadas))
                                    <button type="button"
                                            wire:click="abrirGaleriaPredeterminadaEdicion"
                                            class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                        Imágenes predeterminadas
                                    </button>
                                @endif
                                @if ($producto->imagen !== null)
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-medium text-red-600 dark:text-red-400">
                                        <input type="checkbox"
                                               wire:model="quitarImagenEdicion"
                                               class="rounded border-stone-300 text-red-600 focus:ring-red-500 dark:border-stone-600 dark:bg-stone-900">
                                        Quitar imagen
                                    </label>
                                @endif
                                @if ($productoImagenPredeterminadaEdicion !== null)
                                    <button type="button"
                                            wire:click="quitarImagenPredeterminadaEdicion"
                                            class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                        Quitar predeterminada
                                    </button>
                                @endif
                            </div>
                            <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">
                                Cuadrada ideal 800×800 px. JPG, PNG o WebP. Máx. 2 MB. Se optimiza automáticamente.
                            </p>
                            @if ($productoImagenEdicion)
                                <label class="mt-2 inline-flex cursor-pointer items-center gap-1.5 text-[11px] font-medium text-stone-600 dark:text-stone-300">
                                    <input type="checkbox"
                                           wire:model="guardarEnPredeterminadasEdicion"
                                           class="rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                                    Guardar esta imagen en imágenes predeterminadas
                                </label>
                            @endif
                            <p wire:loading wire:target="productoImagenEdicion" class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">
                                Subiendo imagen…
                            </p>
                            @error('productoImagenEdicion')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                            @error('productoImagenPredeterminadaEdicion')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="producto-edicion-nombre" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                            Nombre del ítem <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input id="producto-edicion-nombre"
                               type="text"
                               wire:model="productoNombreEdicion"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                        @error('productoNombreEdicion')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="producto-edicion-precio" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                Precio <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input id="producto-edicion-precio"
                                   type="number"
                                   step="0.01"
                                   min="0"
                                   wire:model="productoPrecioEdicion"
                                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                            @error('productoPrecioEdicion')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="producto-edicion-stock" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                Stock <span class="text-xs font-normal text-stone-400">(opcional)</span>
                            </label>
                            <input id="producto-edicion-stock"
                                   type="number"
                                   step="1"
                                   min="0"
                                   wire:model="productoStockEdicion"
                                   placeholder="Vacío = ilimitado"
                                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                            @error('productoStockEdicion')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-stone-100 pt-4 dark:border-stone-700/60">
                        <button type="button"
                                wire:click="solicitarCierreEdicionProducto"
                                class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                            Guardar
                        </button>
                    </div>
                </form>
            @endif
        </x-modal-configuracion>

        <div class="mt-6">
            @if ($categorias->isEmpty())
                <div class="rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Todavía no hay categorías creadas</p>
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Crea la primera con el botón «Nueva categoría».</p>
                </div>
            @else
                <ul class="divide-y divide-stone-100 dark:divide-stone-700/60" role="list">
                    @foreach ($categorias as $categoria)
                        <li wire:key="categoria-{{ $categoria->id }}" class="py-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="min-w-0 truncate text-sm font-medium text-stone-800 dark:text-stone-100">
                                    {{ $categoria->nombre }}
                                    @unless ($categoria->activa)
                                        <span class="ml-2 rounded-full bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-500 dark:bg-stone-700 dark:text-stone-300">Inactiva</span>
                                    @endunless
                                </span>
                                <div class="flex shrink-0 flex-wrap items-center gap-1">
                                    <button type="button"
                                            wire:click="abrirProductos({{ $categoria->id }})"
                                            :aria-expanded="$wire.productosAbiertosId === {{ $categoria->id }} ? 'true' : 'false'"
                                            class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/40">
                                        {{ $productosAbiertosId === $categoria->id ? 'Ocultar ítems' : 'Ítems' }}
                                        ({{ $categoria->productos_count }})
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                             class="size-3.5 transition-transform duration-300"
                                             :class="$wire.productosAbiertosId === {{ $categoria->id }} ? 'rotate-180' : ''"
                                             aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button type="button"
                                            wire:click="iniciarEdicion({{ $categoria->id }})"
                                            title="Editar {{ $categoria->nombre }}"
                                            aria-label="Editar {{ $categoria->nombre }}"
                                            class="rounded-lg bg-sky-50 p-1.5 text-sky-700 shadow-sm ring-1 ring-sky-200 transition-all hover:scale-105 hover:bg-sky-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-1 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50 dark:focus-visible:ring-offset-stone-800">
                                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                        </svg>
                                    </button>
                                    @if ($categoria->activa)
                                        <button type="button"
                                                wire:click="cambiarActiva({{ $categoria->id }})"
                                                title="Dar de baja a {{ $categoria->nombre }}"
                                                aria-label="Dar de baja a {{ $categoria->nombre }}"
                                                class="rounded-lg bg-red-50 p-1.5 text-red-700 shadow-sm ring-1 ring-red-200 transition-all hover:scale-105 hover:bg-red-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-900/50 dark:focus-visible:ring-offset-stone-800">
                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    @else
                                        <button type="button"
                                                wire:click="cambiarActiva({{ $categoria->id }})"
                                                title="Reactivar {{ $categoria->nombre }}"
                                                aria-label="Reactivar {{ $categoria->nombre }}"
                                                class="rounded-lg bg-emerald-50 p-1.5 text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition-all hover:scale-105 hover:bg-emerald-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/50 dark:focus-visible:ring-offset-stone-800">
                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div x-data="{}"
                                 class="grid grid-rows-[0fr] opacity-0 transition-[grid-template-rows,opacity] duration-300 ease-out"
                                 :class="{
                                     'grid-rows-[1fr]': $wire.productosAbiertosId === {{ $categoria->id }},
                                     'opacity-100': $wire.productosAbiertosId === {{ $categoria->id }},
                                     'grid-rows-[0fr]': $wire.productosAbiertosId !== {{ $categoria->id }},
                                     'opacity-0': $wire.productosAbiertosId !== {{ $categoria->id }},
                                 }"
                                 :aria-hidden="$wire.productosAbiertosId === {{ $categoria->id }} ? 'false' : 'true'"
                                 :inert="$wire.productosAbiertosId !== {{ $categoria->id }}">
                                <div class="overflow-hidden">
                                <div wire:key="productos-{{ $categoria->id }}" class="mt-3 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                                            Ítems de «{{ $categoria->nombre }}»
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button"
                                                    wire:click="abrirModalProducto"
                                                    class="inline-flex items-center gap-1 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5" aria-hidden="true">
                                                    <path d="M10 4.5a.75.75 0 0 1 .75.75v4h4a.75.75 0 0 1 0 1.5h-4v4a.75.75 0 0 1-1.5 0v-4h-4a.75.75 0 0 1 0-1.5h4v-4A.75.75 0 0 1 10 4.5Z" />
                                                </svg>
                                                Agregar ítem
                                            </button>
                                            <button type="button"
                                                    wire:click="abrirProductos({{ $categoria->id }})"
                                                    class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-500 hover:bg-stone-200 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white">
                                                Cerrar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-1">
                                        @if ($categoria->productos->isEmpty())
                                            <p class="rounded-xl border border-dashed border-stone-300 px-4 py-6 text-center text-xs text-stone-500 dark:border-stone-600 dark:text-stone-400">
                                                Esta categoría todavía no tiene ítems.
                                            </p>
                                        @else
                                            <ul class="divide-y divide-stone-200 dark:divide-stone-700" role="list">
                                                @foreach ($categoria->productos as $producto)
                                                    <li wire:key="producto-{{ $producto->id }}" class="py-2">
                                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                                                    <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-200 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                                                                        @if ($producto->imagenUrl())
                                                                            <img src="{{ $producto->imagenUrl() }}" alt="{{ $producto->nombre }}" class="size-full object-cover" loading="lazy">
                                                                        @else
                                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                                            </svg>
                                                                        @endif
                                                                    </div>
                                                                    <div class="min-w-0">
                                                                        <div class="flex flex-wrap items-center gap-2">
                                                                            <span class="block truncate text-sm text-stone-700 dark:text-stone-200">
                                                                                {{ $producto->nombre }}
                                                                            </span>
                                                                            @if ($producto->activa)
                                                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Activa</span>
                                                                            @else
                                                                                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-[11px] font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">Baja</span>
                                                                            @endif
                                                                        </div>
                                                                        <span @class([
                                                                            'text-xs font-medium',
                                                                            'text-red-600 dark:text-red-400' => $producto->stock !== null && $producto->stock <= 0,
                                                                            'text-amber-600 dark:text-amber-400' => $producto->stock !== null && $producto->stock > 0 && $producto->stock <= 5,
                                                                            'text-stone-500 dark:text-stone-400' => $producto->stock === null || $producto->stock > 5,
                                                                        ])>
                                                                            Stock: {{ $producto->stock === null ? 'ilimitado' : $producto->stock }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="flex shrink-0 items-center gap-2">
                                                                    <span class="rounded-lg bg-white px-2 py-1 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600">
                                                                        Bs {{ number_format((float) $producto->precio, 2) }}
                                                                    </span>
                                                                    <button type="button"
                                                                            wire:click="iniciarEdicionProducto({{ $producto->id }})"
                                                                            class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-600 hover:bg-stone-200 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                                                                        Editar
                                                                    </button>
                                                                    @if ($producto->activa)
                                                                        <button type="button"
                                                                                wire:click="cambiarActivaProducto({{ $producto->id }})"
                                                                                title="Dar de baja a {{ $producto->nombre }}"
                                                                                aria-label="Dar de baja a {{ $producto->nombre }}"
                                                                                class="rounded-lg bg-red-50 p-1.5 text-red-700 shadow-sm ring-1 ring-red-200 transition-all hover:scale-105 hover:bg-red-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-900/50">
                                                                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                                <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                                            </svg>
                                                                        </button>
                                                                    @else
                                                                        <button type="button"
                                                                                wire:click="cambiarActivaProducto({{ $producto->id }})"
                                                                                title="Reactivar {{ $producto->nombre }}"
                                                                                aria-label="Reactivar {{ $producto->nombre }}"
                                                                                class="rounded-lg bg-emerald-50 p-1.5 text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition-all hover:scale-105 hover:bg-emerald-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/50">
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
                                        @endif
                                    </div>
                                </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{ $categorias->links('components.paginacion-compacta', ['enLivewire' => true]) }}
            @endif
        </div>

        <div class="mt-6 border-t border-stone-100 pt-4 dark:border-stone-700/60">
            <p class="text-xs text-stone-400 dark:text-stone-500">
                Las categorías y los ítems se guardan al instante.
            </p>
        </div>
    </div>

    @if ($mostrarGaleriaPredeterminada)
        @include('livewire.configuracion._galeria-imagenes-predeterminadas', [
            'imagenes' => $imagenesPredeterminadas,
            'seleccionada' => $productoImagenPredeterminada,
            'accionSeleccionar' => 'seleccionarImagenPredeterminada',
            'accionCerrar' => 'cerrarGaleriaPredeterminada',
            'prefijo' => 'crear-'.$productosAbiertosId,
            'tituloId' => 'galeria-predeterminada-crear',
        ])
    @endif

    @if ($mostrarGaleriaPredeterminadaEdicion && $productoEditandoId !== null)
        @include('livewire.configuracion._galeria-imagenes-predeterminadas', [
            'imagenes' => $imagenesPredeterminadas,
            'seleccionada' => $productoImagenPredeterminadaEdicion,
            'accionSeleccionar' => 'seleccionarImagenPredeterminadaEdicion',
            'accionCerrar' => 'cerrarGaleriaPredeterminadaEdicion',
            'prefijo' => 'editar-'.$productoEditandoId,
            'tituloId' => 'galeria-predeterminada-editar',
        ])
    @endif

    <x-modal-confirmar-descartar :abierto="$confirmandoDescartar !== null" />
</div>
