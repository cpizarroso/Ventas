<div x-on:cambio-select-tema.window="$wire.elegirCategoriaSelect($event.detail.id, $event.detail.valor)">
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="border-b border-stone-200 px-4 pt-4 dark:border-stone-700 sm:px-6">
            <nav class="flex gap-1" aria-label="Secciones de productos, categorías y promociones">
                <button type="button"
                        wire:click="cambiarPestana('productos')"
                        @class([
                            'relative rounded-t-lg px-4 py-3 text-sm font-semibold transition-colors',
                            'bg-amber-50 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200' => $pestana === 'productos',
                            'text-stone-500 hover:bg-stone-50 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700/50 dark:hover:text-stone-100' => $pestana !== 'productos',
                        ])
                        @if ($pestana === 'productos') aria-current="page" @endif>
                    Productos
                    @if ($pestana === 'productos')
                        <span class="ml-1 rounded-full bg-amber-200/70 px-2 py-0.5 text-[11px] tabular-nums text-amber-900 dark:bg-amber-800/50 dark:text-amber-100">
                            {{ $productos->total() }}
                        </span>
                    @endif
                </button>

                <button type="button"
                        wire:click="cambiarPestana('categorias')"
                        @class([
                            'relative rounded-t-lg px-4 py-3 text-sm font-semibold transition-colors',
                            'bg-amber-50 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200' => $pestana === 'categorias',
                            'text-stone-500 hover:bg-stone-50 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700/50 dark:hover:text-stone-100' => $pestana !== 'categorias',
                        ])
                        @if ($pestana === 'categorias') aria-current="page" @endif>
                    Categorías
                    @if ($pestana === 'categorias')
                        <span class="ml-1 rounded-full bg-amber-200/70 px-2 py-0.5 text-[11px] tabular-nums text-amber-900 dark:bg-amber-800/50 dark:text-amber-100">
                            {{ $categorias->total() }}
                        </span>
                    @endif
                </button>

                <button type="button"
                        wire:click="cambiarPestana('promociones')"
                        @class([
                            'relative rounded-t-lg px-4 py-3 text-sm font-semibold transition-colors',
                            'bg-amber-50 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200' => $pestana === 'promociones',
                            'text-stone-500 hover:bg-stone-50 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700/50 dark:hover:text-stone-100' => $pestana !== 'promociones',
                        ])
                        @if ($pestana === 'promociones') aria-current="page" @endif>
                    Promociones
                    @if ($pestana === 'promociones')
                        <span class="ml-1 rounded-full bg-amber-200/70 px-2 py-0.5 text-[11px] tabular-nums text-amber-900 dark:bg-amber-800/50 dark:text-amber-100">
                            {{ $promocionesCount }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        @if ($pestana === 'promociones')
            <div class="p-4 sm:p-6">
                <livewire:productos.promociones-admin />
            </div>
        @elseif ($pestana === 'productos')
            <div class="p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Listado de productos</h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Edita una fila con el lápiz, da de baja con la papelera y haz clic en la imagen para ampliarla.
                        </p>
                    </div>
                    <button type="button"
                            wire:click="nuevoProducto"
                            @if ($creandoProducto || $productoEditandoId !== null) disabled @endif
                            class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-amber-400">
                        Nuevo producto
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_14rem]">
                    <div>
                        <label for="buscar-productos" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            Buscar producto
                        </label>
                        <input id="buscar-productos"
                               type="search"
                               wire:model.live.debounce.300ms="buscarProducto"
                               placeholder="Nombre del producto o categoría"
                               @if ($creandoProducto || $productoEditandoId !== null) disabled @endif
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    </div>
                    <div>
                        <x-select-tema id="filtro-categoria"
                                       model="categoriaFiltroId"
                                       label="Categoría"
                                       :options="$categoriasParaFormulario
                                           ->map(fn ($categoriaOpcion) => [
                                               'valor' => (string) $categoriaOpcion->id,
                                               'etiqueta' => $categoriaOpcion->nombre,
                                           ])
                                           ->prepend(['valor' => '', 'etiqueta' => 'Todas'])
                                           ->all()"
                                       :selected="$categoriaFiltroId"
                                       :disabled="$creandoProducto || $productoEditandoId !== null"
                                       class="w-full" />
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                        <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                            <tr>
                                <x-encabezado-ordenable campo="nombre"
                                                        etiqueta="Producto"
                                                        :orden="$productosOrden"
                                                        :direccion="$productosDireccion"
                                                        accion="ordenarProductos"
                                                        :deshabilitado="$creandoProducto || $productoEditandoId !== null" />
                                <x-encabezado-ordenable campo="categoria"
                                                        etiqueta="Categoría"
                                                        :orden="$productosOrden"
                                                        :direccion="$productosDireccion"
                                                        accion="ordenarProductos"
                                                        :deshabilitado="$creandoProducto || $productoEditandoId !== null" />
                                <x-encabezado-ordenable campo="precio"
                                                        etiqueta="Precio"
                                                        alinear="right"
                                                        :orden="$productosOrden"
                                                        :direccion="$productosDireccion"
                                                        accion="ordenarProductos"
                                                        :deshabilitado="$creandoProducto || $productoEditandoId !== null" />
                                <x-encabezado-ordenable campo="stock"
                                                        etiqueta="Stock / mín."
                                                        alinear="right"
                                                        :orden="$productosOrden"
                                                        :direccion="$productosDireccion"
                                                        accion="ordenarProductos"
                                                        :deshabilitado="$creandoProducto || $productoEditandoId !== null" />
                                <th scope="col" class="px-3 py-2.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @if ($creandoProducto)
                                <tr class="bg-amber-50/60 dark:bg-amber-900/10">
                                    <td class="px-3 py-3 align-top">
                                        <div class="flex items-start gap-3">
                                            <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-200 text-stone-400 ring-1 ring-stone-300 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600">
                                                @if ($productoImagen && $productoImagen->isPreviewable())
                                                    <img src="{{ $productoImagen->temporaryUrl() }}" alt="Vista previa" class="size-full object-cover">
                                                @else
                                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <label for="producto-nombre-inline" class="sr-only">Nombre del producto</label>
                                                <input id="producto-nombre-inline"
                                                       type="text"
                                                       wire:model="productoNombre"
                                                       wire:keydown.enter="guardarProductoInline"
                                                       placeholder="Nombre del producto"
                                                       autofocus
                                                       class="w-full min-w-40 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('productoNombre')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                                <label class="mt-1.5 inline-flex cursor-pointer items-center gap-1 rounded-lg bg-sky-50 p-1.5 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-200 transition-all hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50">
                                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                                                    </svg>
                                                    Elegir imagen
                                                    <input type="file" wire:model="productoImagen" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                                </label>
                                                @error('productoImagen')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top">
                                        <x-select-tema id="producto-categoria-nuevo"
                                                       model="productoCategoriaId"
                                                       aria-label="Categoría del producto"
                                                       :options="$categoriasParaFormulario
                                                           ->map(fn ($categoriaOpcion) => [
                                                               'valor' => (string) $categoriaOpcion->id,
                                                               'etiqueta' => $categoriaOpcion->nombre,
                                                           ])
                                                           ->prepend(['valor' => '', 'etiqueta' => 'Seleccionar'])
                                                           ->all()"
                                                       :selected="$productoCategoriaId"
                                                       class="w-full min-w-32" />
                                        @error('productoCategoriaId')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-3 py-3 align-top text-right">
                                        <div class="flex flex-col items-end">
                                            <label for="producto-precio-inline" class="sr-only">Precio</label>
                                            <input id="producto-precio-inline"
                                                   type="number"
                                                   step="0.01"
                                                   min="0"
                                                   wire:model="productoPrecio"
                                                   placeholder="0.00"
                                                   class="w-24 rounded-lg border border-stone-300 bg-white px-2 py-2 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                            @error('productoPrecio')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 align-top text-right">
                                        <div class="flex flex-col items-end gap-1.5">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[11px] font-medium text-stone-500 dark:text-stone-400">Ilimitado</span>
                                                <button type="button"
                                                        role="switch"
                                                        aria-checked="{{ $productoStockIlimitado ? 'true' : 'false' }}"
                                                        aria-label="Stock ilimitado"
                                                        wire:click="alternarStockIlimitadoInline"
                                                        class="relative h-5 w-9 shrink-0 rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-stone-800 {{ $productoStockIlimitado ? 'bg-emerald-500' : 'bg-stone-300 dark:bg-stone-600' }}">
                                                    <span class="absolute top-0.5 size-4 rounded-full bg-white shadow-sm transition-all {{ $productoStockIlimitado ? 'left-4' : 'left-0.5' }}"></span>
                                                </button>
                                            </div>
                                            @unless ($productoStockIlimitado)
                                                <label for="producto-stock-inline" class="sr-only">Stock</label>
                                                <input id="producto-stock-inline"
                                                       type="number"
                                                       step="1"
                                                       min="0"
                                                       wire:model="productoStock"
                                                       placeholder="0"
                                                       class="w-20 rounded-lg border border-stone-300 bg-white px-2 py-2 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                            @endunless
                                            @error('productoStock')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                            @enderror
                                            <label for="producto-stock-min-nuevo" class="sr-only">Stock mínimo</label>
                                            <input id="producto-stock-min-nuevo"
                                                   type="number"
                                                   step="1"
                                                   min="0"
                                                   wire:model="productoStockMin"
                                                   placeholder="Mín: {{ $stockMinimoNegocio }}"
                                                   title="Stock mínimo. Vacío para heredar el del negocio ({{ $stockMinimoNegocio }})."
                                                   class="w-20 rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-right text-xs shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                            @error('productoStockMin')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                    wire:click="guardarProductoInline"
                                                    data-preservar-scroll
                                                    title="Guardar producto"
                                                    aria-label="Guardar producto"
                                                    class="rounded-lg bg-emerald-500 p-1.5 text-white shadow-sm ring-1 ring-emerald-600/20 transition-all hover:scale-105 hover:bg-emerald-600 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-500 dark:text-white dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-stone-800">
                                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    wire:click="cancelarEdicionProducto"
                                                    data-preservar-scroll
                                                    title="Cancelar"
                                                    aria-label="Cancelar edición"
                                                    class="rounded-lg bg-stone-200 p-1.5 text-stone-700 shadow-sm ring-1 ring-stone-300/70 transition-all hover:scale-105 hover:bg-stone-300 active:scale-95 focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:bg-stone-700 dark:text-stone-100 dark:ring-stone-600 dark:hover:bg-stone-600 dark:focus-visible:ring-offset-stone-800">
                                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @forelse ($productos as $producto)
                                @if ($productoEditandoId === $producto->id)
                                    <tr wire:key="producto-editando-{{ $producto->id }}" class="bg-amber-50/60 dark:bg-amber-900/10">
                                        <td class="px-3 py-3 align-top">
                                            <div class="flex items-start gap-3">
                                                <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-200 text-stone-400 ring-1 ring-stone-300 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600">
                                                    @if ($quitarImagen)
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                                        </svg>
                                                    @elseif ($productoImagen && $productoImagen->isPreviewable())
                                                        <img src="{{ $productoImagen->temporaryUrl() }}" alt="Vista previa" class="size-full object-cover">
                                                    @elseif ($producto->imagenUrl())
                                                        <img src="{{ $producto->imagenUrl() }}" alt="" class="size-full object-cover">
                                                    @else
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <label for="producto-nombre-inline" class="sr-only">Nombre del producto</label>
                                                    <input id="producto-nombre-inline"
                                                           type="text"
                                                           wire:model="productoNombre"
                                                           wire:keydown.enter="guardarProductoInline"
                                                           autofocus
                                                           class="w-full min-w-40 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                    @error('productoNombre')
                                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                    @enderror
                                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                        <label class="inline-flex cursor-pointer items-center gap-1 rounded-lg bg-sky-50 p-1.5 text-[11px] font-semibold text-sky-700 ring-1 ring-sky-200 transition-all hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50">
                                                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                                                            </svg>
                                                            Cambiar imagen
                                                            <input type="file" wire:model="productoImagen" accept="image/jpeg,image/png,image/webp" class="sr-only">
                                                        </label>
                                                        @if ($producto->imagen)
                                                            <button type="button"
                                                                    wire:click="alternarQuitarImagen"
                                                                    class="rounded-lg p-1.5 text-[11px] font-semibold {{ $quitarImagen ? 'bg-red-50 text-red-700 ring-1 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800' : 'bg-stone-100 text-stone-600 ring-1 ring-stone-200 hover:bg-stone-200 dark:bg-stone-700/50 dark:text-stone-300 dark:ring-stone-600' }}">
                                                                {{ $quitarImagen ? 'Restaurar' : 'Quitar' }}
                                                            </button>
                                                        @endif
                                                    </div>
                                                    @error('productoImagen')
                                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <x-select-tema id="producto-categoria-{{ $producto->id }}"
                                                           model="productoCategoriaId"
                                                           aria-label="Categoría de {{ $producto->nombre }}"
                                                           :options="$categoriasParaFormulario
                                                               ->map(fn ($categoriaOpcion) => [
                                                                   'valor' => (string) $categoriaOpcion->id,
                                                                   'etiqueta' => $categoriaOpcion->nombre,
                                                               ])
                                                               ->prepend(['valor' => '', 'etiqueta' => 'Seleccionar'])
                                                               ->all()"
                                                           :selected="$productoCategoriaId"
                                                           class="w-full min-w-32" />
                                            @error('productoCategoriaId')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-3 py-3 align-top text-right">
                                            <div class="flex flex-col items-end">
                                                <label for="producto-precio-inline" class="sr-only">Precio</label>
                                                <input id="producto-precio-inline"
                                                       type="number"
                                                       step="0.01"
                                                       min="0"
                                                       wire:model="productoPrecio"
                                                       class="w-24 rounded-lg border border-stone-300 bg-white px-2 py-2 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('productoPrecio')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-top text-right">
                                            <div class="flex flex-col items-end gap-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[11px] font-medium text-stone-500 dark:text-stone-400">Ilimitado</span>
                                                    <button type="button"
                                                            role="switch"
                                                            aria-checked="{{ $productoStockIlimitado ? 'true' : 'false' }}"
                                                            aria-label="Stock ilimitado"
                                                            wire:click="alternarStockIlimitadoInline"
                                                            class="relative h-5 w-9 shrink-0 rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-stone-800 {{ $productoStockIlimitado ? 'bg-emerald-500' : 'bg-stone-300 dark:bg-stone-600' }}">
                                                        <span class="absolute top-0.5 size-4 rounded-full bg-white shadow-sm transition-all {{ $productoStockIlimitado ? 'left-4' : 'left-0.5' }}"></span>
                                                    </button>
                                                </div>
                                                @unless ($productoStockIlimitado)
                                                    <label for="producto-stock-inline" class="sr-only">Stock</label>
                                                    <input id="producto-stock-inline"
                                                           type="number"
                                                           step="1"
                                                           min="0"
                                                           wire:model="productoStock"
                                                           class="w-20 rounded-lg border border-stone-300 bg-white px-2 py-2 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @endunless
                                                @error('productoStock')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                                <label for="producto-stock-min-editar" class="sr-only">Stock mínimo</label>
                                                <input id="producto-stock-min-editar"
                                                       type="number"
                                                       step="1"
                                                       min="0"
                                                       wire:model="productoStockMin"
                                                       placeholder="Mín: {{ $stockMinimoNegocio }}"
                                                       title="Stock mínimo. Vacío para heredar el del negocio ({{ $stockMinimoNegocio }})."
                                                       class="w-20 rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-right text-xs shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('productoStockMin')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button"
                                                        wire:click="guardarProductoInline"
                                                        data-preservar-scroll
                                                        title="Guardar producto"
                                                        aria-label="Guardar producto"
                                                        class="rounded-lg bg-emerald-500 p-1.5 text-white shadow-sm ring-1 ring-emerald-600/20 transition-all hover:scale-105 hover:bg-emerald-600 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-500 dark:text-white dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        wire:click="cancelarEdicionProducto"
                                                        data-preservar-scroll
                                                        title="Cancelar edición"
                                                        aria-label="Cancelar edición"
                                                        class="rounded-lg bg-stone-200 p-1.5 text-stone-700 shadow-sm ring-1 ring-stone-300/70 transition-all hover:scale-105 hover:bg-stone-300 active:scale-95 focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:bg-stone-700 dark:text-stone-100 dark:ring-stone-600 dark:hover:bg-stone-600 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    <tr wire:key="producto-{{ $producto->id }}" class="hover:bg-stone-50/70 dark:hover:bg-stone-700/20">
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-3">
                                                @if ($producto->imagenUrl())
                                                    <button type="button"
                                                            wire:click="abrirImagenProducto({{ $producto->id }})"
                                                            data-preservar-scroll
                                                            title="Ampliar imagen de {{ $producto->nombre }}"
                                                            aria-label="Ampliar imagen de {{ $producto->nombre }}"
                                                            class="group relative flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-200 ring-1 ring-stone-300 transition-all hover:scale-105 hover:ring-amber-400 focus-visible:ring-2 focus-visible:ring-amber-500 dark:bg-stone-700 dark:ring-stone-600">
                                                        <img src="{{ $producto->imagenUrl() }}" alt="" class="size-full object-cover">
                                                        <span class="absolute inset-0 flex items-center justify-center bg-stone-900/40 text-white opacity-0 transition-opacity group-hover:opacity-100">
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                                                <path d="M10.5 7.5v6m-3-3h6" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                @else
                                                    <div class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-stone-200 text-stone-400 dark:bg-stone-700 dark:text-stone-500">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-medium text-stone-800 dark:text-stone-100">{{ $producto->nombre }}</span>
                                                    @if ($producto->activa)
                                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Activa</span>
                                                    @else
                                                        <span class="rounded-full bg-stone-200 px-2 py-0.5 text-[11px] font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">Baja</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-stone-600 dark:text-stone-300">
                                            {{ $producto->categoria?->nombre ?? '—' }}
                                            @if ($producto->categoria && ! $producto->categoria->activa)
                                                <span class="ml-1 text-xs text-amber-600 dark:text-amber-400">(inactiva)</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right font-medium tabular-nums text-stone-800 dark:text-stone-100">
                                            Bs {{ number_format((float) $producto->precio, 2) }}
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            @php
                                                $stockMinimo = $producto->stockMinimo();
                                            @endphp
                                            <div class="flex flex-wrap items-center justify-end gap-1">
                                                @if ($producto->stock === null)
                                                    <span class="text-xs text-stone-500 dark:text-stone-400">Ilimitado</span>
                                                @else
                                                    @if ($producto->stock <= 0)
                                                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">Agotado</span>
                                                    @elseif ($producto->stock <= $stockMinimo)
                                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Bajo stock</span>
                                                    @endif
                                                    <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-semibold tabular-nums text-stone-700 dark:bg-stone-700 dark:text-stone-200">
                                                        Stock: {{ $producto->stock }}
                                                    </span>
                                                @endif
                                                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-xs font-medium tabular-nums text-stone-600 dark:bg-stone-700 dark:text-stone-300"
                                                      title="{{ $producto->stock_min === null ? 'Heredado del negocio' : 'Umbral propio' }}">
                                                    Mín: {{ $stockMinimo }}{{ $producto->stock_min !== null ? ' (propio)' : '' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button"
                                                        wire:click="abrirAdicionStock({{ $producto->id }})"
                                                        data-preservar-scroll
                                                        title="Adicionar stock a {{ $producto->nombre }}"
                                                        aria-label="Adicionar stock a {{ $producto->nombre }}"
                                                        @if ($creandoProducto || $productoEditandoId !== null) disabled @endif
                                                        class="rounded-lg bg-amber-50 p-1.5 text-amber-700 shadow-sm ring-1 ring-amber-200 transition-all hover:scale-105 hover:bg-amber-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800 dark:hover:bg-amber-900/50 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 5v14M5 12h14" />
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        wire:click="editarProducto({{ $producto->id }})"
                                                        data-preservar-scroll
                                                        title="Editar {{ $producto->nombre }}"
                                                        aria-label="Editar {{ $producto->nombre }}"
                                                        @if ($creandoProducto || $productoEditandoId !== null) disabled @endif
                                                        class="rounded-lg bg-sky-50 p-1.5 text-sky-700 shadow-sm ring-1 ring-sky-200 transition-all hover:scale-105 hover:bg-sky-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                    </svg>
                                                </button>
                                                @if ($producto->activa)
                                                    <button type="button"
                                                            wire:click="cambiarActivaProducto({{ $producto->id }})"
                                                            data-preservar-scroll
                                                            title="Dar de baja a {{ $producto->nombre }}"
                                                            aria-label="Dar de baja a {{ $producto->nombre }}"
                                                            @if ($creandoProducto || $productoEditandoId !== null) disabled @endif
                                                            class="rounded-lg bg-red-50 p-1.5 text-red-700 shadow-sm ring-1 ring-red-200 transition-all hover:scale-105 hover:bg-red-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-900/50 dark:focus-visible:ring-offset-stone-800">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            wire:click="cambiarActivaProducto({{ $producto->id }})"
                                                            data-preservar-scroll
                                                            title="Reactivar {{ $producto->nombre }}"
                                                            aria-label="Reactivar {{ $producto->nombre }}"
                                                            @if ($creandoProducto || $productoEditandoId !== null) disabled @endif
                                                            class="rounded-lg bg-emerald-50 p-1.5 text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition-all hover:scale-105 hover:bg-emerald-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/50 dark:focus-visible:ring-offset-stone-800">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                @unless ($creandoProducto)
                                    <tr>
                                        <td colspan="5" class="px-3 py-10 text-center text-sm text-stone-500 dark:text-stone-400">
                                            @if (trim($buscarProducto) !== '' || $categoriaFiltroId !== null)
                                                No hay productos que coincidan con la búsqueda.
                                            @else
                                                Todavía no hay productos. Crea el primero con «Nuevo producto».
                                            @endif
                                        </td>
                                    </tr>
                                @endunless
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @unless ($creandoProducto || $productoEditandoId !== null)
                    <div class="mt-4">
                        {{ $productos->links('components.paginacion-compacta', ['enLivewire' => true]) }}
                    </div>
                @endunless
            </div>
        @else
            <div class="p-4 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Listado de categorías</h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Edita una fila con el lápiz. Dar de baja la oculta de la venta sin eliminarla ni borrar sus productos.
                        </p>
                    </div>
                    <button type="button"
                            wire:click="nuevaCategoria"
                            @if ($creandoCategoria || $categoriaEditandoId !== null) disabled @endif
                            class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-amber-400">
                        Nueva categoría
                    </button>
                </div>

                <div class="mt-5 max-w-sm">
                    <label for="buscar-categorias" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Buscar categoría
                    </label>
                    <input id="buscar-categorias"
                           type="search"
                           wire:model.live.debounce.300ms="buscarCategoria"
                           placeholder="Nombre de la categoría"
                           @if ($creandoCategoria || $categoriaEditandoId !== null) disabled @endif
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 disabled:cursor-not-allowed disabled:opacity-50 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                        <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                            <tr>
                                <x-encabezado-ordenable campo="nombre"
                                                        etiqueta="Categoría"
                                                        :orden="$categoriasOrden"
                                                        :direccion="$categoriasDireccion"
                                                        accion="ordenarCategorias"
                                                        :deshabilitado="$creandoCategoria || $categoriaEditandoId !== null" />
                                <x-encabezado-ordenable campo="activa"
                                                        etiqueta="Estado"
                                                        :orden="$categoriasOrden"
                                                        :direccion="$categoriasDireccion"
                                                        accion="ordenarCategorias"
                                                        :deshabilitado="$creandoCategoria || $categoriaEditandoId !== null" />
                                <x-encabezado-ordenable campo="productos"
                                                        etiqueta="Productos"
                                                        alinear="right"
                                                        :orden="$categoriasOrden"
                                                        :direccion="$categoriasDireccion"
                                                        accion="ordenarCategorias"
                                                        :deshabilitado="$creandoCategoria || $categoriaEditandoId !== null" />
                                <x-encabezado-ordenable campo="orden"
                                                        etiqueta="Orden"
                                                        alinear="right"
                                                        :orden="$categoriasOrden"
                                                        :direccion="$categoriasDireccion"
                                                        accion="ordenarCategorias"
                                                        :deshabilitado="$creandoCategoria || $categoriaEditandoId !== null" />
                                <th scope="col" class="px-3 py-2.5 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @if ($creandoCategoria)
                                <tr class="bg-amber-50/60 dark:bg-amber-900/10">
                                    <td class="px-3 py-3 align-top">
                                        <label for="categoria-nombre-inline" class="sr-only">Nombre de la categoría</label>
                                        <input id="categoria-nombre-inline"
                                               type="text"
                                               wire:model="categoriaNombre"
                                               wire:keydown.enter="guardarCategoriaInline"
                                               placeholder="Nombre de la nueva categoría"
                                               autofocus
                                               class="w-full min-w-48 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                        @error('categoriaNombre')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="px-3 py-3 align-top">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    role="switch"
                                                    aria-checked="{{ $categoriaActiva ? 'true' : 'false' }}"
                                                    aria-label="Estado de la categoría"
                                                    wire:click="alternarActivaInline"
                                                    class="relative h-5 w-9 shrink-0 rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-stone-800 {{ $categoriaActiva ? 'bg-emerald-500' : 'bg-stone-300 dark:bg-stone-600' }}">
                                                <span class="absolute top-0.5 size-4 rounded-full bg-white shadow-sm transition-all {{ $categoriaActiva ? 'left-4' : 'left-0.5' }}"></span>
                                            </button>
                                            <span class="text-xs font-medium text-stone-600 dark:text-stone-300">
                                                {{ $categoriaActiva ? 'Activa' : 'Inactiva' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right text-stone-400 dark:text-stone-500">—</td>
                                    <td class="px-3 py-3 align-top text-right">
                                        <div class="flex flex-col items-end">
                                            <label for="categoria-orden-inline" class="sr-only">Orden</label>
                                            <input id="categoria-orden-inline"
                                                   type="number"
                                                   step="1"
                                                   min="0"
                                                   wire:model="categoriaOrden"
                                                   class="w-20 rounded-lg border border-stone-300 bg-white px-2 py-2 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                            @error('categoriaOrden')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                    wire:click="guardarCategoriaInline"
                                                    data-preservar-scroll
                                                    title="Guardar categoría"
                                                    aria-label="Guardar categoría"
                                                    class="rounded-lg bg-emerald-500 p-1.5 text-white shadow-sm ring-1 ring-emerald-600/20 transition-all hover:scale-105 hover:bg-emerald-600 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-500 dark:text-white dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-stone-800">
                                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="m4.5 12.75 6 6 9-13.5" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    wire:click="cancelarEdicionCategoria"
                                                    data-preservar-scroll
                                                    title="Cancelar"
                                                    aria-label="Cancelar edición"
                                                    class="rounded-lg bg-stone-200 p-1.5 text-stone-700 shadow-sm ring-1 ring-stone-300/70 transition-all hover:scale-105 hover:bg-stone-300 active:scale-95 focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:bg-stone-700 dark:text-stone-100 dark:ring-stone-600 dark:hover:bg-stone-600 dark:focus-visible:ring-offset-stone-800">
                                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                            @forelse ($categorias as $categoria)
                                @if ($categoriaEditandoId === $categoria->id)
                                    <tr wire:key="categoria-editando-{{ $categoria->id }}" class="bg-amber-50/60 dark:bg-amber-900/10">
                                        <td class="px-3 py-3 align-top">
                                            <label for="categoria-nombre-inline" class="sr-only">Nombre de la categoría</label>
                                            <input id="categoria-nombre-inline"
                                                   type="text"
                                                   wire:model="categoriaNombre"
                                                   wire:keydown.enter="guardarCategoriaInline"
                                                   autofocus
                                                   class="w-full min-w-48 rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                            @error('categoriaNombre')
                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                            @enderror
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <div class="flex items-center gap-2">
                                                <button type="button"
                                                        role="switch"
                                                        aria-checked="{{ $categoriaActiva ? 'true' : 'false' }}"
                                                        aria-label="Estado de la categoría"
                                                        wire:click="alternarActivaInline"
                                                        class="relative h-5 w-9 shrink-0 rounded-full transition-colors focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-stone-800 {{ $categoriaActiva ? 'bg-emerald-500' : 'bg-stone-300 dark:bg-stone-600' }}">
                                                    <span class="absolute top-0.5 size-4 rounded-full bg-white shadow-sm transition-all {{ $categoriaActiva ? 'left-4' : 'left-0.5' }}"></span>
                                                </button>
                                                <span class="text-xs font-medium text-stone-600 dark:text-stone-300">
                                                    {{ $categoriaActiva ? 'Activa' : 'Inactiva' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums text-stone-500 dark:text-stone-400">{{ $categoria->productos_count }}</td>
                                        <td class="px-3 py-3 align-top text-right">
                                            <div class="flex flex-col items-end">
                                                <label for="categoria-orden-inline" class="sr-only">Orden</label>
                                                <input id="categoria-orden-inline"
                                                       type="number"
                                                       step="1"
                                                       min="0"
                                                       wire:model="categoriaOrden"
                                                       class="w-20 rounded-lg border border-stone-300 bg-white px-2 py-2 text-right text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                                                @error('categoriaOrden')
                                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button"
                                                        wire:click="guardarCategoriaInline"
                                                        data-preservar-scroll
                                                        title="Guardar categoría"
                                                        aria-label="Guardar categoría"
                                                        class="rounded-lg bg-emerald-500 p-1.5 text-white shadow-sm ring-1 ring-emerald-600/20 transition-all hover:scale-105 hover:bg-emerald-600 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 dark:bg-emerald-500 dark:text-white dark:hover:bg-emerald-400 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                </button>
                                                <button type="button"
                                                        wire:click="cancelarEdicionCategoria"
                                                        data-preservar-scroll
                                                        title="Cancelar edición"
                                                        aria-label="Cancelar edición"
                                                        class="rounded-lg bg-stone-200 p-1.5 text-stone-700 shadow-sm ring-1 ring-stone-300/70 transition-all hover:scale-105 hover:bg-stone-300 active:scale-95 focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 dark:bg-stone-700 dark:text-stone-100 dark:ring-stone-600 dark:hover:bg-stone-600 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                    <tr wire:key="categoria-{{ $categoria->id }}" class="hover:bg-stone-50/70 dark:hover:bg-stone-700/20">
                                        <td class="px-3 py-3 font-medium text-stone-800 dark:text-stone-100">{{ $categoria->nombre }}</td>
                                        <td class="px-3 py-3">
                                            @if ($categoria->activa)
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Activa</span>
                                            @else
                                                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">Inactiva</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-right tabular-nums text-stone-700 dark:text-stone-200">{{ $categoria->productos_count }}</td>
                                        <td class="px-3 py-3 text-right tabular-nums text-stone-600 dark:text-stone-300">{{ $categoria->orden }}</td>
                                        <td class="px-3 py-3 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button type="button"
                                                        wire:click="editarCategoria({{ $categoria->id }})"
                                                        data-preservar-scroll
                                                        title="Editar {{ $categoria->nombre }}"
                                                        aria-label="Editar {{ $categoria->nombre }}"
                                                        @if ($creandoCategoria || $categoriaEditandoId !== null) disabled @endif
                                                        class="rounded-lg bg-sky-50 p-1.5 text-sky-700 shadow-sm ring-1 ring-sky-200 transition-all hover:scale-105 hover:bg-sky-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50 dark:focus-visible:ring-offset-stone-800">
                                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                    </svg>
                                                </button>
                                                @if ($categoria->activa)
                                                    <button type="button"
                                                            wire:click="cambiarActivaCategoria({{ $categoria->id }})"
                                                            data-preservar-scroll
                                                            title="Dar de baja a {{ $categoria->nombre }}"
                                                            aria-label="Dar de baja a {{ $categoria->nombre }}"
                                                            @if ($creandoCategoria || $categoriaEditandoId !== null) disabled @endif
                                                            class="rounded-lg bg-amber-50 p-1.5 text-amber-700 shadow-sm ring-1 ring-amber-200 transition-all hover:scale-105 hover:bg-amber-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800 dark:hover:bg-amber-900/50 dark:focus-visible:ring-offset-stone-800">
                                                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 1-4.243-4.243" />
                                                        </svg>
                                                    </button>
                                                @else
                                                    <button type="button"
                                                            wire:click="cambiarActivaCategoria({{ $categoria->id }})"
                                                            data-preservar-scroll
                                                            title="Reactivar {{ $categoria->nombre }}"
                                                            aria-label="Reactivar {{ $categoria->nombre }}"
                                                            @if ($creandoCategoria || $categoriaEditandoId !== null) disabled @endif
                                                            class="rounded-lg bg-emerald-50 p-1.5 text-emerald-700 shadow-sm ring-1 ring-emerald-200 transition-all hover:scale-105 hover:bg-emerald-100 active:scale-95 focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/50 dark:focus-visible:ring-offset-stone-800">
                                                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                @unless ($creandoCategoria)
                                    <tr>
                                        <td colspan="5" class="px-3 py-10 text-center text-sm text-stone-500 dark:text-stone-400">
                                            @if (trim($buscarCategoria) !== '')
                                                No hay categorías que coincidan con la búsqueda.
                                            @else
                                                Todavía no hay categorías. Crea la primera con «Nueva categoría».
                                            @endif
                                        </td>
                                    </tr>
                                @endunless
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @unless ($creandoCategoria || $categoriaEditandoId !== null)
                    <div class="mt-4">
                        {{ $categorias->links('components.paginacion-compacta', ['enLivewire' => true]) }}
                    </div>
                @endunless
            </div>
        @endif
    </div>

    <x-modal-confirmar-descartar id="modal-descartar-categoria"
                                 :abierto="$confirmandoDescartarCategoria"
                                 mantener="mantenerCambiosCategoria"
                                 descartar="descartarCambiosCategoria" />

    <x-modal-confirmar-descartar id="modal-descartar-producto"
                                 :abierto="$confirmandoDescartarProducto"
                                 mantener="mantenerCambiosProducto"
                                 descartar="descartarCambiosProducto" />

    <x-modal-configuracion id="modal-imagen-producto"
                           :titulo="'Imagen de '.$imagenAmpliadaNombre"
                           descripcion="Vista ampliada de la imagen del producto."
                           cerrar="cerrarImagenAmpliada"
                           :abierto="$imagenAmpliadaUrl !== null"
                           ancho="max-w-3xl">
        @if ($imagenAmpliadaUrl)
            <img src="{{ $imagenAmpliadaUrl }}"
                 alt="Imagen ampliada de {{ $imagenAmpliadaNombre }}"
                 class="mx-auto max-h-[70vh] w-auto rounded-xl bg-stone-100 object-contain dark:bg-stone-900">
        @endif
    </x-modal-configuracion>

    <x-modal-configuracion id="modal-adicionar-stock"
                           :titulo="$productoAdicionStock ? 'Adicionar stock — '.$productoAdicionStock->nombre : 'Adicionar stock'"
                           descripcion="El stock se suma al valor actual del producto."
                           cerrar="cerrarAdicionStock"
                           :abierto="$productoAdicionStock !== null"
                           ancho="max-w-md">
        @if ($productoAdicionStock)
            @php
                $stockActual = $productoAdicionStock->stock;
                $cantidadAdicion = $adicionStockCantidad === '' ? 0 : (int) $adicionStockCantidad;
                $stockFinal = $stockActual === null ? $cantidadAdicion : $stockActual + $cantidadAdicion;
            @endphp

            <div class="space-y-4">
                <div class="rounded-xl bg-stone-50 p-3 dark:bg-stone-900/50">
                    <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Stock actual</p>
                    <p class="mt-0.5 text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">
                        {{ $stockActual === null ? 'Ilimitado' : $stockActual }}
                    </p>
                    @if ($stockActual === null)
                        <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">
                            Este producto pasará de ilimitado a stock finito.
                        </p>
                    @endif
                </div>

                <div>
                    <label for="adicion-stock-cantidad" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                        {{ $stockActual === null ? 'Nuevo stock' : 'Unidades a agregar' }}
                    </label>
                    <input id="adicion-stock-cantidad"
                           type="number"
                           step="1"
                           min="1"
                           max="999999"
                           wire:model.live="adicionStockCantidad"
                           wire:keydown.enter="adicionarStock"
                           placeholder="0"
                           autofocus
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('adicionStockCantidad')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                    <p class="text-xs font-medium text-amber-800 dark:text-amber-300">
                        @if ($adicionStockCantidad === '')
                            Ingresa una cantidad para ver el resultado.
                        @else
                            @if ($stockActual !== null)
                                {{ $stockActual }} + {{ $cantidadAdicion }} =
                            @endif
                            <span class="font-bold tabular-nums">{{ $stockFinal }}</span> unidades.
                        @endif
                    </p>
                </div>

                <p class="text-[11px] text-stone-400 dark:text-stone-500">
                    Aviso de stock mínimo: {{ $productoAdicionStock->stockMinimo() }} unidades.
                </p>

                <div class="flex justify-end gap-2 border-t border-stone-100 pt-4 dark:border-stone-700">
                    <button type="button"
                            wire:click="cerrarAdicionStock"
                            class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="adicionarStock"
                            class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
                        Guardar stock
                    </button>
                </div>
            </div>
        @endif
    </x-modal-configuracion>
</div>
