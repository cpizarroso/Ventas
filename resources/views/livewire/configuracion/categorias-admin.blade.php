<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
        <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Categorías de venta</h2>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
            Crea y administra las categorías que aparecerán en la pantalla principal de ventas.
        </p>

        <form wire:submit="crear" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-start">
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

        <div class="mt-6">
            @if ($categorias->isEmpty())
                <div class="rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Todavía no hay categorías creadas</p>
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Agrega la primera categoría con el formulario de arriba.</p>
                </div>
            @else
                <ul class="divide-y divide-stone-100 dark:divide-stone-700/60" role="list">
                    @foreach ($categorias as $categoria)
                        <li wire:key="categoria-{{ $categoria->id }}" class="py-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                @if ($editandoId === $categoria->id)
                                    <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                                        <label for="nombre-{{ $categoria->id }}" class="sr-only">Nuevo nombre</label>
                                        <input id="nombre-{{ $categoria->id }}"
                                               type="text"
                                               wire:model="nombreEdicion"
                                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500"
                                               wire:keydown.enter="guardarEdicion"
                                               wire:keydown.escape="cancelarEdicion">
                                        @error('nombreEdicion')
                                            <p class="w-full text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex shrink-0 gap-2">
                                        <button type="button"
                                                wire:click="guardarEdicion"
                                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                            Guardar
                                        </button>
                                        <button type="button"
                                                wire:click="cancelarEdicion"
                                                class="rounded-lg bg-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                            Cancelar
                                        </button>
                                    </div>
                                @elseif ($eliminandoId === $categoria->id)
                                    <div class="flex w-full flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900 dark:bg-red-900/30">
                                        <p class="text-sm font-medium text-red-800 dark:text-red-300">
                                            ¿Eliminar «{{ $categoria->nombre }}»? También se eliminarán sus ítems. Esta acción no se puede deshacer.
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
                                @else
                                    <span class="min-w-0 truncate text-sm font-medium text-stone-800 dark:text-stone-100">
                                        {{ $categoria->nombre }}
                                        @unless ($categoria->activa)
                                            <span class="ml-2 rounded-full bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-500 dark:bg-stone-700 dark:text-stone-300">Inactiva</span>
                                        @endunless
                                    </span>
                                    <div class="flex shrink-0 flex-wrap gap-1">
                                        <button type="button"
                                                wire:click="abrirProductos({{ $categoria->id }})"
                                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/40">
                                            {{ $productosAbiertosId === $categoria->id ? 'Ocultar ítems' : 'Ítems' }}
                                            ({{ $categoria->productos_count }})
                                        </button>
                                        <button type="button"
                                                wire:click="iniciarEdicion({{ $categoria->id }})"
                                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                                            Editar
                                        </button>
                                        <button type="button"
                                                wire:click="confirmarEliminacion({{ $categoria->id }})"
                                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/40">
                                            Eliminar
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if ($productosAbiertosId === $categoria->id)
                                <div wire:key="productos-{{ $categoria->id }}" class="mt-3 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                                            Ítems de «{{ $categoria->nombre }}»
                                        </h3>
                                        <button type="button"
                                                wire:click="abrirProductos({{ $categoria->id }})"
                                                class="rounded-lg px-2 py-1 text-xs font-semibold text-stone-500 hover:bg-stone-200 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white">
                                            Cerrar
                                        </button>
                                    </div>

                                    <form wire:submit="crearProducto" class="mt-4 space-y-3">
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
                                                    <label for="producto-imagen-{{ $categoria->id }}"
                                                           class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                                        Elegir imagen
                                                    </label>
                                                    <input id="producto-imagen-{{ $categoria->id }}"
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

                                        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-[1fr_8rem_10rem_auto] md:items-start">
                                            <div>
                                                <label for="producto-nombre-{{ $categoria->id }}" class="sr-only">Nombre del ítem</label>
                                                <input id="producto-nombre-{{ $categoria->id }}"
                                                       type="text"
                                                       wire:model="productoNombre"
                                                       placeholder="Nombre del ítem"
                                                       class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('productoNombre')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="producto-precio-{{ $categoria->id }}" class="sr-only">Precio</label>
                                                <input id="producto-precio-{{ $categoria->id }}"
                                                       type="number"
                                                       step="0.01"
                                                       min="0"
                                                       wire:model="productoPrecio"
                                                       placeholder="Precio"
                                                       class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('productoPrecio')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="producto-stock-{{ $categoria->id }}" class="sr-only">Stock</label>
                                                <input id="producto-stock-{{ $categoria->id }}"
                                                       type="number"
                                                       step="1"
                                                       min="0"
                                                       wire:model="productoStock"
                                                       placeholder="Stock (vacío = ilimitado)"
                                                       class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('productoStock')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            <button type="submit"
                                                    class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                                                Agregar ítem
                                            </button>
                                        </div>
                                    </form>

                                    <div class="mt-4">
                                        @if ($productos->isEmpty())
                                            <p class="rounded-xl border border-dashed border-stone-300 px-4 py-6 text-center text-xs text-stone-500 dark:border-stone-600 dark:text-stone-400">
                                                Esta categoría todavía no tiene ítems.
                                            </p>
                                        @else
                                            <ul class="divide-y divide-stone-200 dark:divide-stone-700" role="list">
                                                @foreach ($productos as $producto)
                                                    <li wire:key="producto-{{ $producto->id }}" class="py-2">
                                                        @if ($productoEditandoId === $producto->id)
                                                            <div class="space-y-3">
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
                                                                            <label for="producto-edicion-imagen-{{ $producto->id }}"
                                                                                   class="inline-flex cursor-pointer items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 ring-1 ring-stone-200 hover:bg-stone-100 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-700">
                                                                                Cambiar imagen
                                                                            </label>
                                                                            <input id="producto-edicion-imagen-{{ $producto->id }}"
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

                                                                <div class="flex flex-col gap-2 md:flex-row md:items-start">
                                                                    <div class="flex-1">
                                                                        <label for="producto-edicion-nombre-{{ $producto->id }}" class="sr-only">Nuevo nombre</label>
                                                                        <input id="producto-edicion-nombre-{{ $producto->id }}"
                                                                               type="text"
                                                                               wire:model="productoNombreEdicion"
                                                                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                                        @error('productoNombreEdicion')
                                                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="md:w-36">
                                                                        <label for="producto-edicion-precio-{{ $producto->id }}" class="sr-only">Nuevo precio</label>
                                                                        <input id="producto-edicion-precio-{{ $producto->id }}"
                                                                               type="number"
                                                                               step="0.01"
                                                                               min="0"
                                                                               wire:model="productoPrecioEdicion"
                                                                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                                        @error('productoPrecioEdicion')
                                                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="md:w-36">
                                                                        <label for="producto-edicion-stock-{{ $producto->id }}" class="sr-only">Nuevo stock</label>
                                                                        <input id="producto-edicion-stock-{{ $producto->id }}"
                                                                               type="number"
                                                                               step="1"
                                                                               wire:model="productoStockEdicion"
                                                                               placeholder="Stock (vacío = ilimitado)"
                                                                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                                        @error('productoStockEdicion')
                                                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                                        @enderror
                                                                    </div>
                                                                    <div class="flex shrink-0 gap-2">
                                                                        <button type="button"
                                                                                wire:click="guardarEdicionProducto"
                                                                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                                                                            Guardar
                                                                        </button>
                                                                        <button type="button"
                                                                                wire:click="cancelarEdicionProducto"
                                                                                class="rounded-lg bg-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                                                            Cancelar
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @elseif ($productoEliminandoId === $producto->id)
                                                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-red-200 bg-red-50 px-3 py-2 dark:border-red-900 dark:bg-red-900/30">
                                                                <p class="text-xs font-medium text-red-800 dark:text-red-300">
                                                                    ¿Eliminar «{{ $producto->nombre }}»?
                                                                </p>
                                                                <div class="flex shrink-0 gap-2">
                                                                    <button type="button"
                                                                            wire:click="eliminarProducto"
                                                                            class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500">
                                                                        Sí, eliminar
                                                                    </button>
                                                                    <button type="button"
                                                                            wire:click="cancelarEliminacionProducto"
                                                                            class="rounded-lg bg-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                                                        Cancelar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @else
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
                                                                        <span class="block truncate text-sm text-stone-700 dark:text-stone-200">
                                                                            {{ $producto->nombre }}
                                                                        </span>
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
                                                                    <button type="button"
                                                                            wire:click="confirmarEliminacionProducto({{ $producto->id }})"
                                                                            class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/40">
                                                                        Eliminar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="mt-6 flex flex-col gap-2 border-t border-stone-100 pt-4 dark:border-stone-700/60 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-stone-400 dark:text-stone-500">
                Las categorías y los ítems se guardan al instante.
            </p>
            <button type="button"
                    wire:click="guardarCambios"
                    class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                Guardar cambios
            </button>
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
</div>
