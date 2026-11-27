<?php

namespace App\Livewire\Productos;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Models\Categoria;
use App\Models\Producto;
use App\Support\CatalogoCache;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Admin extends Component
{
    use FlashesSavedStatus, OptimizaImagenes, WithFileUploads, WithPagination;

    private const PESTANAS = ['productos', 'categorias', 'promociones'];

    /** @var array<int, string> */
    private const CAMPOS_ORDEN_PRODUCTOS = ['nombre', 'categoria', 'precio', 'stock'];

    /** @var array<int, string> */
    private const CAMPOS_ORDEN_CATEGORIAS = ['nombre', 'activa', 'productos', 'orden'];

    #[Url(as: 'pestana')]
    public string $pestana = 'productos';

    #[Url(as: 'buscar-productos')]
    public string $buscarProducto = '';

    #[Url(as: 'buscar-categorias')]
    public string $buscarCategoria = '';

    public ?int $categoriaFiltroId = null;

    public string $productosOrden = 'nombre';

    public string $productosDireccion = 'asc';

    public string $categoriasOrden = 'orden';

    public string $categoriasDireccion = 'asc';

    public ?int $productoEditandoId = null;

    public bool $creandoProducto = false;

    public string $productoNombre = '';

    public int|string $productoCategoriaId = '';

    public string $productoPrecio = '';

    public string $productoStock = '';

    public bool $productoStockIlimitado = false;

    /** Empty means inherit the business-wide minimum. */
    public string $productoStockMin = '';

    /** Product currently shown in the "add stock" modal. */
    public ?int $adicionStockProductoId = null;

    public string $adicionStockCantidad = '';

    /** @var TemporaryUploadedFile|null */
    public $productoImagen = null;

    public bool $quitarImagen = false;

    /**
     * Values captured when the inline row was opened, used to detect unsaved changes.
     *
     * @var array<string, mixed>
     */
    public array $productoOriginal = [];

    public bool $confirmandoDescartarProducto = false;

    public ?int $categoriaEditandoId = null;

    public bool $creandoCategoria = false;

    public string $categoriaNombre = '';

    public bool $categoriaActiva = true;

    public int $categoriaOrden = 0;

    /**
     * Values captured when the inline row was opened, used to detect unsaved changes.
     *
     * @var array<string, mixed>
     */
    public array $categoriaOriginal = [];

    public bool $confirmandoDescartarCategoria = false;

    public ?string $imagenAmpliadaUrl = null;

    public string $imagenAmpliadaNombre = '';

    public function mount(): void
    {
        if (! in_array($this->pestana, self::PESTANAS, true)) {
            $this->pestana = 'productos';
        }
    }

    /**
     * Keep the paginator out of the other pages' query strings.
     *
     * @return array<string, array<string, mixed>>
     */
    public function queryString(): array
    {
        return [
            'paginators.page' => ['as' => 'pagina-productos', 'history' => false],
        ];
    }

    public function updatingBuscarProducto(): void
    {
        if ($this->editandoProducto()) {
            return;
        }

        $this->resetPage();
    }

    public function elegirCategoriaSelect(string $id, int|string $valor): void
    {
        if (! str_starts_with($id, 'filtro-categoria') && ! str_starts_with($id, 'producto-categoria-')) {
            return;
        }

        if ($id === 'filtro-categoria') {
            $this->categoriaFiltroId = $valor === '' ? null : (int) $valor;
            $this->resetPage();

            return;
        }

        if (str_starts_with($id, 'producto-categoria-') && ($this->creandoProducto || $this->editandoProducto())) {
            $this->productoCategoriaId = $valor === '' ? '' : (int) $valor;
        }
    }

    public function updatingBuscarCategoria(): void
    {
        if ($this->editandoCategoria()) {
            return;
        }

        $this->resetPage();
    }

    public function updatedCategoriaFiltroId(): void
    {
        if ($this->categoriaFiltroId === 0) {
            $this->categoriaFiltroId = null;
        }

        if ($this->editandoProducto()) {
            return;
        }

        $this->resetPage();
    }

    /**
     * Toggle the sort direction of the products table from its column header.
     */
    public function ordenarProductos(string $campo): void
    {
        if (! in_array($campo, self::CAMPOS_ORDEN_PRODUCTOS, true) || $this->editandoProducto()) {
            return;
        }

        if ($this->productosOrden === $campo) {
            $this->productosDireccion = $this->productosDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->productosOrden = $campo;
            $this->productosDireccion = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Toggle the sort direction of the categories table from its column header.
     */
    public function ordenarCategorias(string $campo): void
    {
        if (! in_array($campo, self::CAMPOS_ORDEN_CATEGORIAS, true) || $this->editandoCategoria()) {
            return;
        }

        if ($this->categoriasOrden === $campo) {
            $this->categoriasDireccion = $this->categoriasDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->categoriasOrden = $campo;
            $this->categoriasDireccion = 'asc';
        }

        $this->resetPage();
    }

    public function cambiarPestana(string $pestana): void
    {
        if (! in_array($pestana, self::PESTANAS, true)) {
            return;
        }

        if ($this->editandoProducto() && $this->productoTieneCambios()) {
            $this->confirmandoDescartarProducto = true;

            return;
        }

        if ($this->editandoCategoria() && $this->categoriaTieneCambios()) {
            $this->confirmandoDescartarCategoria = true;

            return;
        }

        $this->pestana = $pestana;
        $this->resetPage();
        $this->cerrarModales();
    }

    /**
     * Open an inline row at the top of the list to create a product.
     */
    public function nuevoProducto(): void
    {
        $this->authorize('create', Categoria::class);

        $this->limpiarEdicionProducto();
        $this->creandoProducto = true;
        $this->productoStockMin = (string) (Auth::user()->negocio?->stockMinimo()
            ?? config('productos.stock_min_defecto', 5));

        $primeraCategoria = Auth::user()->negocio
            ?->categorias()
            ->where('activa', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->first();

        if ($primeraCategoria !== null) {
            $this->productoCategoriaId = $primeraCategoria->id;
        }

        $this->productoOriginal = $this->productoValores();
    }

    /**
     * Turn an existing row into an editable form.
     */
    public function editarProducto(int $productoId): void
    {
        $producto = $this->productoDelUsuario($productoId);

        $this->authorize('update', $producto);

        $this->limpiarEdicionProducto();
        $this->productoEditandoId = $producto->id;
        $this->productoNombre = $producto->nombre;
        $this->productoCategoriaId = $producto->categoria_id;
        $this->productoPrecio = (string) $producto->precio;
        $this->productoStock = $producto->stock === null ? '' : (string) $producto->stock;
        $this->productoStockIlimitado = $producto->stock === null;
        $this->productoStockMin = $producto->stock_min === null ? '' : (string) $producto->stock_min;
        $this->productoOriginal = $this->productoValores();
    }

    public function guardarProductoInline(): void
    {
        if (! $this->editandoProducto()) {
            return;
        }

        $this->validate([
            'productoNombre' => [
                'required', 'string', 'min:2', 'max:80',
                Rule::unique('productos', 'nombre')
                    ->where('categoria_id', $this->productoCategoriaId)
                    ->ignore($this->productoEditandoId),
            ],
            'productoCategoriaId' => [
                'required', 'integer',
                Rule::exists('categorias', 'id')->where('negocio_id', Auth::user()->negocio_id),
            ],
            'productoPrecio' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'productoStock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'productoStockMin' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'productoImagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'productoNombre.unique' => 'Ya existe un producto con ese nombre en la categoría seleccionada.',
            'productoNombre.required' => 'El nombre del producto es obligatorio.',
            'productoCategoriaId.required' => 'Selecciona una categoría.',
            'productoCategoriaId.exists' => 'La categoría seleccionada no es válida.',
            'productoPrecio.required' => 'El precio es obligatorio.',
            'productoPrecio.decimal' => 'El precio admite como máximo dos decimales.',
            'productoStock.integer' => 'El stock debe ser un número entero.',
            'productoStock.min' => 'El stock no puede ser negativo.',
            'productoStockMin.integer' => 'El stock mínimo debe ser un número entero.',
            'productoStockMin.min' => 'El stock mínimo no puede ser negativo.',
            'productoImagen.image' => 'La imagen debe ser un archivo de imagen válido.',
            'productoImagen.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'productoImagen.max' => 'La imagen no puede superar los 2 MB.',
        ], [
            'productoNombre' => 'nombre',
            'productoCategoriaId' => 'categoría',
            'productoPrecio' => 'precio',
            'productoStock' => 'stock',
            'productoStockMin' => 'stock mínimo',
            'productoImagen' => 'imagen',
        ]);

        $categoria = $this->categoriaDelUsuario((int) $this->productoCategoriaId);
        $producto = $this->productoEditandoId === null
            ? null
            : $this->productoDelUsuario($this->productoEditandoId);

        if ($producto === null) {
            $this->authorize('create', [Producto::class, $categoria]);
        } else {
            $this->authorize('update', $producto);
        }

        $imagen = $producto?->imagen;

        if ($this->productoImagen !== null) {
            $this->eliminarImagen($imagen);
            $imagen = $this->guardarImagenOptimizada($this->productoImagen, 'productos', 'producto');
        } elseif ($this->quitarImagen && $producto !== null) {
            $this->eliminarImagen($imagen);
            $imagen = null;
        }

        $datos = [
            'categoria_id' => $categoria->id,
            'nombre' => trim($this->productoNombre),
            'imagen' => $imagen,
            'precio' => $this->productoPrecio,
            'stock' => $this->productoStockIlimitado || $this->productoStock === ''
                ? null
                : (int) $this->productoStock,
            'stock_min' => $this->productoStockMin === '' ? null : (int) $this->productoStockMin,
        ];

        if ($producto === null) {
            $categoria->productos()->create($datos);
        } else {
            $producto->update($datos);
        }

        $this->limpiarEdicionProducto();
        $this->flashSaved();
    }

    public function updatedProductoImagen(): void
    {
        if ($this->productoImagen !== null) {
            $this->quitarImagen = false;
        }
    }

    /**
     * Toggle the compact stock switch; unlimited products persist a null stock.
     */
    public function alternarStockIlimitadoInline(): void
    {
        if (! $this->editandoProducto()) {
            return;
        }

        $this->productoStockIlimitado = ! $this->productoStockIlimitado;

        if ($this->productoStockIlimitado) {
            $this->productoStock = '';
        }
    }

    public function alternarQuitarImagen(): void
    {
        if (! $this->editandoProducto()) {
            return;
        }

        $this->quitarImagen = ! $this->quitarImagen;

        if ($this->quitarImagen) {
            $this->productoImagen = null;
        }
    }

    /**
     * Close the inline row, asking first when there are unsaved changes.
     */
    public function cancelarEdicionProducto(): void
    {
        if (! $this->editandoProducto()) {
            return;
        }

        if ($this->productoTieneCambios()) {
            $this->confirmandoDescartarProducto = true;

            return;
        }

        $this->limpiarEdicionProducto();
    }

    public function mantenerCambiosProducto(): void
    {
        $this->confirmandoDescartarProducto = false;
    }

    public function descartarCambiosProducto(): void
    {
        $this->confirmandoDescartarProducto = false;
        $this->limpiarEdicionProducto();
    }

    public function abrirImagenProducto(int $productoId): void
    {
        $producto = $this->productoDelUsuario($productoId);
        $url = $producto->imagenUrl();

        if ($url === null || $url === '') {
            return;
        }

        $this->imagenAmpliadaUrl = $url;
        $this->imagenAmpliadaNombre = $producto->nombre;
    }

    public function cerrarImagenAmpliada(): void
    {
        $this->reset('imagenAmpliadaUrl', 'imagenAmpliadaNombre');
    }

    /**
     * Open an inline row at the top of the list to create a category.
     */
    public function nuevaCategoria(): void
    {
        $this->authorize('create', Categoria::class);

        $this->limpiarEdicionCategoria();
        $this->creandoCategoria = true;
        $this->categoriaOriginal = $this->categoriaValores();
    }

    /**
     * Turn an existing row into an editable form.
     */
    public function editarCategoria(int $categoriaId): void
    {
        $categoria = $this->categoriaDelUsuario($categoriaId);

        $this->authorize('update', $categoria);

        $this->limpiarEdicionCategoria();
        $this->categoriaEditandoId = $categoria->id;
        $this->categoriaNombre = $categoria->nombre;
        $this->categoriaActiva = $categoria->activa;
        $this->categoriaOrden = $categoria->orden;
        $this->categoriaOriginal = $this->categoriaValores();
    }

    public function guardarCategoriaInline(): void
    {
        if (! $this->editandoCategoria()) {
            return;
        }

        $this->validate([
            'categoriaNombre' => [
                'required', 'string', 'min:2', 'max:80',
                Rule::unique('categorias', 'nombre')
                    ->where('negocio_id', Auth::user()->negocio_id)
                    ->ignore($this->categoriaEditandoId),
            ],
            'categoriaActiva' => ['boolean'],
            'categoriaOrden' => ['required', 'integer', 'min:0', 'max:9999'],
        ], [
            'categoriaNombre.unique' => 'Ya existe una categoría con ese nombre en este negocio.',
            'categoriaNombre.required' => 'El nombre de la categoría es obligatorio.',
            'categoriaOrden.integer' => 'El orden debe ser un número entero.',
            'categoriaOrden.min' => 'El orden no puede ser negativo.',
        ], [
            'categoriaNombre' => 'nombre',
            'categoriaActiva' => 'activa',
            'categoriaOrden' => 'orden',
        ]);

        $datos = [
            'nombre' => trim($this->categoriaNombre),
            'activa' => $this->categoriaActiva,
            'orden' => $this->categoriaOrden,
        ];

        if ($this->creandoCategoria) {
            $this->authorize('create', Categoria::class);

            Auth::user()->negocio->categorias()->create($datos);
        } else {
            $categoria = $this->categoriaDelUsuario((int) $this->categoriaEditandoId);

            $this->authorize('update', $categoria);

            $categoria->update($datos);
        }

        $this->limpiarEdicionCategoria();
        $this->flashSaved();
    }

    /**
     * Toggle the inline state; it is persisted only when the row is saved.
     */
    public function alternarActivaInline(): void
    {
        if (! $this->editandoCategoria()) {
            return;
        }

        $this->categoriaActiva = ! $this->categoriaActiva;
    }

    /**
     * Close the inline row, asking first when there are unsaved changes.
     */
    public function cancelarEdicionCategoria(): void
    {
        if (! $this->editandoCategoria()) {
            return;
        }

        if ($this->categoriaTieneCambios()) {
            $this->confirmandoDescartarCategoria = true;

            return;
        }

        $this->limpiarEdicionCategoria();
    }

    public function mantenerCambiosCategoria(): void
    {
        $this->confirmandoDescartarCategoria = false;
    }

    public function descartarCambiosCategoria(): void
    {
        $this->confirmandoDescartarCategoria = false;
        $this->limpiarEdicionCategoria();
    }

    /**
     * Deactivate (or reactivate) a category without deleting it or its products.
     */
    public function cambiarActivaCategoria(int $categoriaId): void
    {
        if ($this->editandoCategoria()) {
            return;
        }

        $categoria = $this->categoriaDelUsuario($categoriaId);

        $this->authorize('update', $categoria);

        $activa = ! $categoria->activa;
        $categoria->update(['activa' => $activa]);

        $mensaje = $activa
            ? 'La categoría se activó correctamente.'
            : 'La categoría se dio de baja y no aparecerá en ventas.';

        session()->flash('status', $mensaje);
        $this->dispatch('toast', message: $mensaje);
    }

    /**
     * Deactivate (or reactivate) a product without deleting it or its history.
     */
    public function cambiarActivaProducto(int $productoId): void
    {
        if ($this->editandoProducto()) {
            return;
        }

        $producto = $this->productoDelUsuario($productoId);

        $this->authorize('cambiarActiva', $producto);

        $activa = ! $producto->activa;
        $producto->update(['activa' => $activa]);

        $mensaje = $activa
            ? 'El producto se reactivó y ya aparecerá en ventas.'
            : 'El producto se dio de baja y no aparecerá en ventas.';

        session()->flash('status', $mensaje);
        $this->dispatch('toast', message: $mensaje);
    }

    /**
     * Open the modal used to add units to an existing product.
     */
    public function abrirAdicionStock(int $productoId): void
    {
        if ($this->editandoProducto()) {
            return;
        }

        $producto = $this->productoDelUsuario($productoId);
        $this->authorize('update', $producto);

        $this->adicionStockProductoId = $producto->id;
        $this->adicionStockCantidad = '';
        $this->resetValidation(['adicionStockCantidad']);
    }

    public function cerrarAdicionStock(): void
    {
        $this->reset('adicionStockProductoId', 'adicionStockCantidad');
        $this->resetValidation(['adicionStockCantidad']);
    }

    /**
     * Add units atomically. Unlimited (null) stock becomes finite with the given amount.
     */
    public function adicionarStock(): void
    {
        if ($this->adicionStockProductoId === null) {
            return;
        }

        $this->validate([
            'adicionStockCantidad' => ['required', 'integer', 'min:1', 'max:999999'],
        ], [
            'adicionStockCantidad.required' => 'Indica la cantidad.',
            'adicionStockCantidad.integer' => 'La cantidad debe ser un número entero.',
            'adicionStockCantidad.min' => 'La cantidad debe ser al menos 1.',
            'adicionStockCantidad.max' => 'La cantidad no puede superar 999999.',
        ], [
            'adicionStockCantidad' => 'cantidad',
        ]);

        $producto = $this->productoDelUsuario($this->adicionStockProductoId);
        $this->authorize('update', $producto);

        $cantidad = (int) $this->adicionStockCantidad;

        if ($producto->stock === null) {
            DB::table('productos')->where('id', $producto->id)->update([
                'stock' => $cantidad,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('productos')->where('id', $producto->id)->increment('stock', $cantidad, [
                'updated_at' => now(),
            ]);
        }

        // increment()/update() bypass the model events that normally clear this cache.
        CatalogoCache::invalidar((int) Auth::user()->negocio_id);

        $this->cerrarAdicionStock();
        $this->dispatch('stockActualizado');
        $this->flashSaved();
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;

        $categoriasParaFormulario = $negocio !== null
            ? $negocio->categorias()->orderBy('orden')->orderBy('nombre')->get()
            : collect();

        if ($this->pestana === 'categorias') {
            $ordenCategorias = match ($this->categoriasOrden) {
                'nombre' => 'nombre',
                'activa' => 'activa',
                'productos' => 'productos_count',
                default => 'orden',
            };
            $direccionCategorias = $this->categoriasDireccion === 'desc' ? 'desc' : 'asc';

            $categorias = ($negocio !== null ? $negocio->categorias() : Categoria::query()->whereRaw('1 = 0'))
                ->withCount('productos')
                ->when(trim($this->buscarCategoria) !== '', function ($query): void {
                    $query->where('nombre', 'like', '%'.trim($this->buscarCategoria).'%');
                })
                ->orderBy($ordenCategorias, $direccionCategorias)
                ->orderBy('nombre')
                ->paginate(10);

            $productos = collect();
        } else {
            $direccionProductos = $this->productosDireccion === 'desc' ? 'desc' : 'asc';

            $consultaProductos = Producto::query()
                ->whereHas('categoria', fn ($query) => $query->where('categorias.negocio_id', $negocio?->id ?? 0))
                ->with('categoria.negocio')
                ->when(trim($this->buscarProducto) !== '', function ($query): void {
                    $patron = '%'.trim($this->buscarProducto).'%';
                    $query->where(function ($query) use ($patron): void {
                        $query->where('nombre', 'like', $patron)
                            ->orWhereHas('categoria', fn ($categorias) => $categorias->where('nombre', 'like', $patron));
                    });
                })
                ->when(
                    $this->categoriaFiltroId !== null && $this->categoriaFiltroId > 0,
                    fn ($query) => $query->where('categoria_id', $this->categoriaFiltroId),
                );

            if ($this->productosOrden === 'categoria') {
                $consultaProductos->orderByRaw(
                    '(SELECT categorias.nombre FROM categorias WHERE categorias.id = productos.categoria_id) '.$direccionProductos,
                );
            } elseif ($this->productosOrden === 'stock') {
                $consultaProductos
                    ->orderByRaw('stock IS NULL ASC')
                    ->orderBy('stock', $direccionProductos);
            } else {
                $consultaProductos->orderBy($this->productosOrden, $direccionProductos);
            }

            $productos = $consultaProductos->orderBy('nombre')->paginate(10);

            $categorias = collect();
        }

        $promocionesCount = $negocio !== null ? $negocio->promociones()->count() : 0;

        $productoAdicionStock = null;

        if ($this->adicionStockProductoId !== null) {
            $productoAdicionStock = Producto::query()
                ->whereKey($this->adicionStockProductoId)
                ->whereHas('categoria', fn ($query) => $query->where('categorias.negocio_id', $negocio?->id ?? 0))
                ->with('categoria.negocio')
                ->first();

            if ($productoAdicionStock === null) {
                $this->cerrarAdicionStock();
            }
        }

        $stockMinimoNegocio = $negocio?->stockMinimo() ?? (int) config('productos.stock_min_defecto', 5);

        /** @var Collection<int, Categoria> $categoriasParaFormulario */
        return view('livewire.productos.admin', [
            'productos' => $productos,
            'categorias' => $categorias,
            'categoriasParaFormulario' => $categoriasParaFormulario,
            'promocionesCount' => $promocionesCount,
            'productoAdicionStock' => $productoAdicionStock,
            'stockMinimoNegocio' => $stockMinimoNegocio,
        ]);
    }

    private function cerrarModales(): void
    {
        $this->limpiarEdicionProducto();
        $this->limpiarEdicionCategoria();
        $this->cerrarImagenAmpliada();
        $this->cerrarAdicionStock();
        $this->resetValidation();
    }

    private function editandoProducto(): bool
    {
        return $this->creandoProducto || $this->productoEditandoId !== null;
    }

    /**
     * @return array{nombre: string, categoria: int|string, precio: string, stock: string, ilimitado: bool, stock_min: string}
     */
    private function productoValores(): array
    {
        return [
            'nombre' => $this->productoNombre,
            'categoria' => $this->productoCategoriaId,
            'precio' => $this->productoPrecio,
            'stock' => $this->productoStock,
            'ilimitado' => $this->productoStockIlimitado,
            'stock_min' => $this->productoStockMin,
        ];
    }

    private function productoTieneCambios(): bool
    {
        return ($this->productoOriginal['nombre'] ?? '') !== $this->productoNombre
            || (string) ($this->productoOriginal['categoria'] ?? '') !== (string) $this->productoCategoriaId
            || ($this->productoOriginal['precio'] ?? '') !== $this->productoPrecio
            || ($this->productoOriginal['stock'] ?? '') !== $this->productoStock
            || (bool) ($this->productoOriginal['ilimitado'] ?? false) !== $this->productoStockIlimitado
            || ($this->productoOriginal['stock_min'] ?? '') !== $this->productoStockMin
            || $this->productoImagen !== null
            || $this->quitarImagen;
    }

    private function limpiarEdicionProducto(): void
    {
        $this->reset(
            'productoEditandoId',
            'creandoProducto',
            'productoNombre',
            'productoCategoriaId',
            'productoPrecio',
            'productoStock',
            'productoStockIlimitado',
            'productoStockMin',
            'productoImagen',
            'quitarImagen',
            'productoOriginal',
            'confirmandoDescartarProducto',
        );
        $this->resetValidation();
    }

    private function editandoCategoria(): bool
    {
        return $this->creandoCategoria || $this->categoriaEditandoId !== null;
    }

    /**
     * @return array{nombre: string, activa: bool, orden: int}
     */
    private function categoriaValores(): array
    {
        return [
            'nombre' => $this->categoriaNombre,
            'activa' => $this->categoriaActiva,
            'orden' => $this->categoriaOrden,
        ];
    }

    private function categoriaTieneCambios(): bool
    {
        return ($this->categoriaOriginal['nombre'] ?? '') !== $this->categoriaNombre
            || (bool) ($this->categoriaOriginal['activa'] ?? true) !== $this->categoriaActiva
            || (int) ($this->categoriaOriginal['orden'] ?? 0) !== $this->categoriaOrden;
    }

    private function limpiarEdicionCategoria(): void
    {
        $this->reset(
            'categoriaEditandoId',
            'creandoCategoria',
            'categoriaNombre',
            'categoriaActiva',
            'categoriaOrden',
            'categoriaOriginal',
            'confirmandoDescartarCategoria',
        );
        $this->resetValidation();
        $this->categoriaActiva = true;
        $this->categoriaOrden = 0;
    }

    private function categoriaDelUsuario(int $categoriaId): Categoria
    {
        $user = Auth::user();

        abort_if($user->negocio_id === null, 403, 'Tu usuario no tiene un negocio asignado.');

        $categoria = $user->negocio->categorias()->whereKey($categoriaId)->first();

        abort_if($categoria === null, 404);

        return $categoria;
    }

    private function productoDelUsuario(int $productoId): Producto
    {
        $user = Auth::user();

        abort_if($user->negocio_id === null, 403, 'Tu usuario no tiene un negocio asignado.');

        $producto = Producto::query()
            ->whereKey($productoId)
            ->whereHas('categoria', fn ($query) => $query->where('categorias.negocio_id', $user->negocio_id))
            ->first();

        abort_if($producto === null, 404);

        return $producto;
    }
}
