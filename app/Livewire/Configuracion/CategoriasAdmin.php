<?php

namespace App\Livewire\Configuracion;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Support\ImagenesPredeterminadas as Catalogo;
use App\Support\ImagenPublica;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CategoriasAdmin extends Component
{
    use FlashesSavedStatus, OptimizaImagenes, WithFileUploads, WithPagination;

    public string $nombre = '';

    public ?int $editandoId = null;

    public string $nombreEdicion = '';

    public ?int $eliminandoId = null;

    public ?int $productosAbiertosId = null;

    public string $productoNombre = '';

    public string $productoPrecio = '';

    public string $productoStock = '';

    /** @var TemporaryUploadedFile|null */
    public $productoImagen = null;

    public ?string $productoImagenPredeterminada = null;

    public bool $guardarEnPredeterminadas = false;

    public bool $mostrarGaleriaPredeterminada = false;

    public ?int $productoEditandoId = null;

    public string $productoNombreEdicion = '';

    public string $productoPrecioEdicion = '';

    public string $productoStockEdicion = '';

    /** @var TemporaryUploadedFile|null */
    public $productoImagenEdicion = null;

    public ?string $productoImagenPredeterminadaEdicion = null;

    public bool $guardarEnPredeterminadasEdicion = false;

    public bool $mostrarGaleriaPredeterminadaEdicion = false;

    public bool $quitarImagenEdicion = false;

    public ?int $productoEliminandoId = null;

    public bool $modalAbierto = false;

    public bool $modalProductoAbierto = false;

    /**
     * Original values captured when a modal is opened, used to detect unsaved changes.
     *
     * @var array<string, mixed>
     */
    public array $original = [];

    /**
     * Which modal is waiting for the "discard changes" confirmation: categoria, producto or item.
     */
    public ?string $confirmandoDescartar = null;

    #[Url(as: 'buscar-categorias')]
    public string $buscar = '';

    /**
     * Alias the paginator so this component does not clash with the other configuration lists.
     *
     * @return array<string, array<string, mixed>>
     */
    public function queryString(): array
    {
        return [
            'paginators.page' => ['as' => 'pagina-categorias', 'history' => false],
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
        $this->reset('productosAbiertosId');
        $this->resetProductos();
    }

    public function updatingPaginators(int $page, string $pageName): void
    {
        $this->reset('productosAbiertosId');
        $this->resetProductos();
    }

    public function abrirModal(): void
    {
        $this->authorize('create', Categoria::class);

        $this->reset('nombre', 'editandoId', 'nombreEdicion');
        $this->resetValidation();
        $this->modalAbierto = true;
        $this->original = [
            'tipo' => 'categoria',
            'nombre' => $this->nombre,
            'nombreEdicion' => $this->nombreEdicion,
        ];
    }

    /**
     * Ask before closing the category modal when the user edited something.
     */
    public function solicitarCierreCategoria(): void
    {
        if ($this->hayCambiosCategoria()) {
            $this->confirmandoDescartar = 'categoria';

            return;
        }

        $this->cerrarCategoria();
    }

    public function cerrarModal(): void
    {
        $this->solicitarCierreCategoria();
    }

    /**
     * Close the category modal and forget its original values.
     */
    private function cerrarCategoria(): void
    {
        $this->reset('nombre', 'editandoId', 'nombreEdicion', 'modalAbierto');
        $this->resetValidation();
        $this->original = [];
        $this->confirmandoDescartar = null;
    }

    /**
     * Create a new category for the authenticated user's business.
     */
    public function crear(): void
    {
        $user = Auth::user();

        $this->authorize('create', Categoria::class);

        $this->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:80', $this->reglaUnicaNombre()],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre en este negocio.',
        ], [
            'nombre' => 'nombre',
        ]);

        $user->negocio->categorias()->create([
            'nombre' => trim($this->nombre),
        ]);

        $this->reset('nombre', 'modalAbierto');
        $this->original = [];

        $this->flashSaved();
    }

    /**
     * Start editing a category name.
     */
    public function iniciarEdicion(int $categoriaId): void
    {
        $categoria = $this->categoriaDelUsuario($categoriaId);

        $this->authorize('update', $categoria);

        $this->editandoId = $categoria->id;
        $this->nombreEdicion = $categoria->nombre;
        $this->eliminandoId = null;
        $this->modalAbierto = true;
        $this->original = [
            'tipo' => 'categoria',
            'nombre' => $this->nombre,
            'nombreEdicion' => $this->nombreEdicion,
        ];
    }

    public function cancelarEdicion(): void
    {
        $this->solicitarCierreCategoria();
    }

    /**
     * Persist the edited category name.
     */
    public function guardarEdicion(): void
    {
        $categoria = $this->categoriaDelUsuario((int) $this->editandoId);

        $this->authorize('update', $categoria);

        $this->validate([
            'nombreEdicion' => [
                'required',
                'string',
                'min:2',
                'max:80',
                $this->reglaUnicaNombre($categoria->id),
            ],
        ], [
            'nombreEdicion.unique' => 'Ya existe una categoría con ese nombre en este negocio.',
        ], [
            'nombreEdicion' => 'nombre',
        ]);

        $categoria->update([
            'nombre' => trim($this->nombreEdicion),
        ]);

        $this->reset('editandoId', 'nombreEdicion', 'modalAbierto');
        $this->original = [];

        $this->flashSaved();
    }

    /**
     * Ask for confirmation before deleting a category.
     */
    public function confirmarEliminacion(int $categoriaId): void
    {
        $categoria = $this->categoriaDelUsuario($categoriaId);

        $this->authorize('delete', $categoria);

        $this->eliminandoId = $categoria->id;
        $this->reset('editandoId', 'nombreEdicion', 'modalAbierto');
        $this->original = [];
    }

    public function cancelarEliminacion(): void
    {
        $this->reset('eliminandoId');
    }

    /**
     * Delete the confirmed category and its products.
     */
    public function eliminar(): void
    {
        $categoria = $this->categoriaDelUsuario((int) $this->eliminandoId);

        $this->authorize('delete', $categoria);

        $categoria->productos()
            ->whereNotNull('imagen')
            ->pluck('imagen')
            ->each(fn (string $imagen) => $this->eliminarImagenProducto($imagen));

        $categoria->delete();

        $this->reset('eliminandoId');

        if ($this->productosAbiertosId === $categoria->id) {
            $this->resetProductos();
        }

        session()->flash('status', 'La categoría se eliminó correctamente.');
    }

    /**
     * Open or close the products panel of a category.
     */
    public function abrirProductos(int $categoriaId): void
    {
        $categoria = $this->categoriaDelUsuario($categoriaId);

        $this->authorize('create', [Producto::class, $categoria]);

        if ($this->productosAbiertosId === $categoria->id) {
            $this->resetProductos();

            return;
        }

        $this->resetProductos();

        $this->productosAbiertosId = $categoria->id;
        $this->reset('editandoId', 'nombreEdicion', 'eliminandoId', 'modalAbierto', 'modalProductoAbierto');
        $this->original = [];
    }

    /**
     * Open the modal to create a product in the open category.
     */
    public function abrirModalProducto(): void
    {
        $categoria = $this->categoriaDelUsuario((int) $this->productosAbiertosId);

        $this->authorize('create', [Producto::class, $categoria]);

        $this->reset(
            'productoNombre',
            'productoPrecio',
            'productoStock',
            'productoImagen',
            'productoImagenPredeterminada',
            'guardarEnPredeterminadas',
            'mostrarGaleriaPredeterminada',
            'productoEditandoId',
            'productoEliminandoId',
        );
        $this->resetValidation();

        $this->modalProductoAbierto = true;
        $this->original = [
            'tipo' => 'producto',
            'productoNombre' => $this->productoNombre,
            'productoPrecio' => $this->productoPrecio,
            'productoStock' => $this->productoStock,
        ];
    }

    /**
     * Ask before closing the create-product modal when the user edited something.
     */
    public function solicitarCierreProducto(): void
    {
        if ($this->hayCambiosProducto()) {
            $this->confirmandoDescartar = 'producto';

            return;
        }

        $this->cerrarProducto();
    }

    public function cerrarModalProducto(): void
    {
        $this->solicitarCierreProducto();
    }

    /**
     * Close the create-product modal and forget its original values.
     */
    private function cerrarProducto(): void
    {
        $this->reset(
            'modalProductoAbierto',
            'productoNombre',
            'productoPrecio',
            'productoStock',
            'productoImagen',
            'productoImagenPredeterminada',
            'guardarEnPredeterminadas',
            'mostrarGaleriaPredeterminada',
        );
        $this->resetValidation();
        $this->original = [];
        $this->confirmandoDescartar = null;
    }

    public function abrirGaleriaPredeterminada(): void
    {
        $this->mostrarGaleriaPredeterminada = true;
    }

    public function cerrarGaleriaPredeterminada(): void
    {
        $this->mostrarGaleriaPredeterminada = false;
    }

    /**
     * Select a predefined image for a new product.
     */
    public function seleccionarImagenPredeterminada(int $indice): void
    {
        $archivo = $this->imagenesPredeterminadas()[$indice] ?? null;

        if ($archivo === null) {
            return;
        }

        $this->productoImagenPredeterminada = $archivo;
        $this->productoImagen = null;
        $this->mostrarGaleriaPredeterminada = false;
    }

    public function quitarImagenPredeterminada(): void
    {
        $this->productoImagenPredeterminada = null;
    }

    public function abrirGaleriaPredeterminadaEdicion(): void
    {
        $this->mostrarGaleriaPredeterminadaEdicion = true;
    }

    public function cerrarGaleriaPredeterminadaEdicion(): void
    {
        $this->mostrarGaleriaPredeterminadaEdicion = false;
    }

    /**
     * Select a predefined image while editing a product.
     */
    public function seleccionarImagenPredeterminadaEdicion(int $indice): void
    {
        $archivo = $this->imagenesPredeterminadas()[$indice] ?? null;

        if ($archivo === null) {
            return;
        }

        $this->productoImagenPredeterminadaEdicion = $archivo;
        $this->productoImagenEdicion = null;
        $this->quitarImagenEdicion = false;
        $this->mostrarGaleriaPredeterminadaEdicion = false;
    }

    public function quitarImagenPredeterminadaEdicion(): void
    {
        $this->productoImagenPredeterminadaEdicion = null;
    }

    public function updatedProductoImagen(): void
    {
        if ($this->productoImagen !== null) {
            $this->productoImagenPredeterminada = null;
        }
    }

    public function updatedProductoImagenEdicion(): void
    {
        if ($this->productoImagenEdicion !== null) {
            $this->productoImagenPredeterminadaEdicion = null;
            $this->quitarImagenEdicion = false;
        }
    }

    public function updatedQuitarImagenEdicion(): void
    {
        if ($this->quitarImagenEdicion) {
            $this->productoImagenEdicion = null;
            $this->productoImagenPredeterminadaEdicion = null;
        }
    }

    /**
     * Create a product inside the open category.
     */
    public function crearProducto(): void
    {
        $categoria = $this->categoriaDelUsuario((int) $this->productosAbiertosId);

        $this->authorize('create', [Producto::class, $categoria]);

        $this->validate([
            'productoNombre' => [
                'required',
                'string',
                'min:2',
                'max:80',
                Rule::unique('productos', 'nombre')->where('categoria_id', $categoria->id),
            ],
            'productoPrecio' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'productoStock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'productoImagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'productoImagenPredeterminada' => ['nullable', 'string', Rule::in($this->imagenesPredeterminadas())],
        ], [
            'productoNombre.unique' => 'Ya existe un ítem con ese nombre en esta categoría.',
            'productoPrecio.decimal' => 'El precio admite como máximo dos decimales.',
            'productoStock.integer' => 'El stock debe ser un número entero.',
            'productoStock.min' => 'El stock no puede ser negativo.',
            'productoImagen.image' => 'La imagen debe ser un archivo de imagen válido.',
            'productoImagen.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'productoImagen.max' => 'La imagen no puede superar los 2 MB.',
            'productoImagenPredeterminada.in' => 'La imagen predeterminada seleccionada no es válida.',
        ], [
            'productoNombre' => 'nombre',
            'productoPrecio' => 'precio',
            'productoStock' => 'stock',
            'productoImagen' => 'imagen',
            'productoImagenPredeterminada' => 'imagen predeterminada',
        ]);

        $imagen = null;

        if ($this->productoImagen !== null) {
            $imagen = $this->guardarImagenProducto($this->productoImagen);

            if ($this->guardarEnPredeterminadas) {
                $this->guardarImagenEnCatalogo($this->productoImagen);
            }
        } elseif ($this->productoImagenPredeterminada !== null) {
            $imagen = $this->guardarImagenPredeterminada($this->productoImagenPredeterminada);
        }

        $categoria->productos()->create([
            'nombre' => trim($this->productoNombre),
            'imagen' => $imagen,
            'precio' => $this->productoPrecio,
            'stock' => $this->productoStock === '' ? null : (int) $this->productoStock,
        ]);

        $this->reset(
            'modalProductoAbierto',
            'productoNombre',
            'productoPrecio',
            'productoStock',
            'productoImagen',
            'productoImagenPredeterminada',
            'guardarEnPredeterminadas',
            'mostrarGaleriaPredeterminada',
        );
        $this->resetValidation();
        $this->original = [];

        $this->flashSaved();
    }

    /**
     * Start editing a product of the open category.
     */
    public function iniciarEdicionProducto(int $productoId): void
    {
        $producto = $this->productoDelUsuario($productoId);

        $this->authorize('update', $producto);

        $this->productoEditandoId = $producto->id;
        $this->productoNombreEdicion = $producto->nombre;
        $this->productoPrecioEdicion = (string) $producto->precio;
        $this->productoStockEdicion = $producto->stock === null ? '' : (string) $producto->stock;
        $this->productoImagenEdicion = null;
        $this->productoImagenPredeterminadaEdicion = null;
        $this->guardarEnPredeterminadasEdicion = false;
        $this->mostrarGaleriaPredeterminadaEdicion = false;
        $this->quitarImagenEdicion = false;
        $this->productoEliminandoId = null;
        $this->resetValidation();
        $this->original = [
            'tipo' => 'item',
            'productoNombreEdicion' => $this->productoNombreEdicion,
            'productoPrecioEdicion' => $this->productoPrecioEdicion,
            'productoStockEdicion' => $this->productoStockEdicion,
        ];
    }

    /**
     * Product shown by the edit modal.
     */
    public function productoEditando(): ?Producto
    {
        if ($this->productoEditandoId === null) {
            return null;
        }

        return $this->productoDelUsuario($this->productoEditandoId);
    }

    /**
     * Ask before closing the edit-product modal when the user edited something.
     */
    public function solicitarCierreEdicionProducto(): void
    {
        if ($this->hayCambiosEdicionProducto()) {
            $this->confirmandoDescartar = 'item';

            return;
        }

        $this->cerrarEdicionProducto();
    }

    public function cancelarEdicionProducto(): void
    {
        $this->solicitarCierreEdicionProducto();
    }

    /**
     * Close the edit-product modal and forget its original values.
     */
    private function cerrarEdicionProducto(): void
    {
        $this->reset(
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoImagenEdicion',
            'productoImagenPredeterminadaEdicion',
            'guardarEnPredeterminadasEdicion',
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
        );
        $this->resetValidation();
        $this->original = [];
        $this->confirmandoDescartar = null;
    }

    /**
     * Whether the category modal has values the user changed since it was opened.
     */
    private function hayCambiosCategoria(): bool
    {
        return ($this->original['nombre'] ?? '') !== $this->nombre
            || ($this->original['nombreEdicion'] ?? '') !== $this->nombreEdicion;
    }

    /**
     * Whether the create-product modal has unsaved values.
     */
    private function hayCambiosProducto(): bool
    {
        return ($this->original['productoNombre'] ?? '') !== $this->productoNombre
            || ($this->original['productoPrecio'] ?? '') !== $this->productoPrecio
            || ($this->original['productoStock'] ?? '') !== $this->productoStock
            || $this->productoImagen !== null
            || $this->productoImagenPredeterminada !== null;
    }

    /**
     * Whether the edit-product modal has values the user changed since it was opened.
     */
    private function hayCambiosEdicionProducto(): bool
    {
        return ($this->original['productoNombreEdicion'] ?? '') !== $this->productoNombreEdicion
            || ($this->original['productoPrecioEdicion'] ?? '') !== $this->productoPrecioEdicion
            || ($this->original['productoStockEdicion'] ?? '') !== $this->productoStockEdicion
            || $this->productoImagenEdicion !== null
            || $this->productoImagenPredeterminadaEdicion !== null
            || $this->quitarImagenEdicion;
    }

    /**
     * Close the modal that was waiting for confirmation, discarding its changes.
     */
    public function descartarCambios(): void
    {
        match ($this->confirmandoDescartar) {
            'categoria' => $this->cerrarCategoria(),
            'producto' => $this->cerrarProducto(),
            'item' => $this->cerrarEdicionProducto(),
            default => null,
        };
    }

    /**
     * Keep editing after the confirmation prompt.
     */
    public function mantenerCambios(): void
    {
        $this->confirmandoDescartar = null;
    }

    /**
     * Persist the edited product.
     */
    public function guardarEdicionProducto(): void
    {
        $producto = $this->productoDelUsuario((int) $this->productoEditandoId);

        $this->authorize('update', $producto);

        $this->validate([
            'productoNombreEdicion' => [
                'required',
                'string',
                'min:2',
                'max:80',
                Rule::unique('productos', 'nombre')
                    ->where('categoria_id', $producto->categoria_id)
                    ->ignore($producto->id),
            ],
            'productoPrecioEdicion' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'productoStockEdicion' => ['nullable', 'integer', 'min:-999999', 'max:999999'],
            'productoImagenEdicion' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'productoImagenPredeterminadaEdicion' => ['nullable', 'string', Rule::in($this->imagenesPredeterminadas())],
        ], [
            'productoNombreEdicion.unique' => 'Ya existe un ítem con ese nombre en esta categoría.',
            'productoPrecioEdicion.decimal' => 'El precio admite como máximo dos decimales.',
            'productoStockEdicion.integer' => 'El stock debe ser un número entero.',
            'productoImagenEdicion.image' => 'La imagen debe ser un archivo de imagen válido.',
            'productoImagenEdicion.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'productoImagenEdicion.max' => 'La imagen no puede superar los 2 MB.',
            'productoImagenPredeterminadaEdicion.in' => 'La imagen predeterminada seleccionada no es válida.',
        ], [
            'productoNombreEdicion' => 'nombre',
            'productoPrecioEdicion' => 'precio',
            'productoStockEdicion' => 'stock',
            'productoImagenEdicion' => 'imagen',
            'productoImagenPredeterminadaEdicion' => 'imagen predeterminada',
        ]);

        $imagen = $producto->imagen;

        if ($this->productoImagenEdicion !== null) {
            $this->eliminarImagenProducto($producto->imagen);

            $imagen = $this->guardarImagenProducto($this->productoImagenEdicion);

            if ($this->guardarEnPredeterminadasEdicion) {
                $this->guardarImagenEnCatalogo($this->productoImagenEdicion);
            }
        } elseif ($this->productoImagenPredeterminadaEdicion !== null) {
            $this->eliminarImagenProducto($producto->imagen);

            $imagen = $this->guardarImagenPredeterminada($this->productoImagenPredeterminadaEdicion);
        } elseif ($this->quitarImagenEdicion) {
            $this->eliminarImagenProducto($producto->imagen);

            $imagen = null;
        }

        $producto->update([
            'nombre' => trim($this->productoNombreEdicion),
            'imagen' => $imagen,
            'precio' => $this->productoPrecioEdicion,
            'stock' => $this->productoStockEdicion === '' ? null : (int) $this->productoStockEdicion,
        ]);

        $this->reset(
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoImagenEdicion',
            'productoImagenPredeterminadaEdicion',
            'guardarEnPredeterminadasEdicion',
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
        );
        $this->original = [];

        $this->flashSaved();
    }

    /**
     * Ask for confirmation before deleting a product.
     */
    public function confirmarEliminacionProducto(int $productoId): void
    {
        $producto = $this->productoDelUsuario($productoId);

        $this->authorize('delete', $producto);

        $this->productoEliminandoId = $producto->id;
        $this->reset(
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoImagenEdicion',
            'productoImagenPredeterminadaEdicion',
            'guardarEnPredeterminadasEdicion',
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
        );
        $this->original = [];
    }

    public function cancelarEliminacionProducto(): void
    {
        $this->reset('productoEliminandoId');
    }

    /**
     * Delete the confirmed product.
     */
    public function eliminarProducto(): void
    {
        $producto = $this->productoDelUsuario((int) $this->productoEliminandoId);

        $this->authorize('delete', $producto);

        $this->eliminarImagenProducto($producto->imagen);

        $producto->delete();

        $this->reset('productoEliminandoId');

        session()->flash('status', 'El ítem se eliminó correctamente.');
    }

    /**
     * Confirm that the current categories and items are saved.
     */
    public function guardarCambios(): void
    {
        $this->authorize('create', Categoria::class);

        $this->resetValidation();

        $this->flashSaved();
    }

    public function placeholder(): View
    {
        return view('components.configuracion-modulo-placeholder', ['titulo' => 'categorías e ítems']);
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;
        $buscar = trim($this->buscar);

        $categorias = ($negocio !== null ? $negocio->categorias() : Categoria::query()->whereRaw('1 = 0'))
            ->withCount('productos')
            ->with(['productos' => fn ($query) => $query->orderBy('nombre')])
            ->when($buscar !== '', fn ($query) => $query->where('nombre', 'like', '%'.$buscar.'%'))
            ->orderBy('orden')
            ->orderBy('nombre')
            ->paginate(10);

        $productos = $this->productosAbiertosId === null
            ? collect()
            : ($categorias->firstWhere('id', $this->productosAbiertosId)?->productos ?? collect());

        $tamano = (int) config('productos.tamano_predeterminada_defecto', 512);

        $imagenesPredeterminadas = array_map(
            fn (string $id): array => [
                'archivo' => $id,
                'url' => Catalogo::url($id, $tamano),
            ],
            $this->imagenesPredeterminadas(),
        );

        return view('livewire.configuracion.categorias-admin', [
            'categorias' => $categorias,
            'productos' => $productos,
            'imagenesPredeterminadas' => $imagenesPredeterminadas,
        ]);
    }

    /**
     * Scope a category lookup to the authenticated user's business.
     */
    private function categoriaDelUsuario(int $categoriaId): Categoria
    {
        $user = Auth::user();

        abort_if($user->negocio_id === null, 403, 'Tu usuario no tiene un negocio asignado.');

        $categoria = $user->negocio->categorias()->whereKey($categoriaId)->first();

        abort_if($categoria === null, 404);

        return $categoria;
    }

    /**
     * Scope a product lookup to the authenticated user's business.
     */
    private function productoDelUsuario(int $productoId): Producto
    {
        $user = Auth::user();

        abort_if($user->negocio_id === null, 403, 'Tu usuario no tiene un negocio asignado.');

        $producto = Producto::query()
            ->whereKey($productoId)
            ->whereHas('categoria', fn ($query) => $query->where('negocio_id', $user->negocio_id))
            ->first();

        abort_if($producto === null, 404);

        return $producto;
    }

    private function resetProductos(): void
    {
        $this->reset(
            'productosAbiertosId',
            'modalProductoAbierto',
            'productoNombre',
            'productoPrecio',
            'productoStock',
            'productoImagen',
            'productoImagenPredeterminada',
            'guardarEnPredeterminadas',
            'mostrarGaleriaPredeterminada',
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoImagenEdicion',
            'productoImagenPredeterminadaEdicion',
            'guardarEnPredeterminadasEdicion',
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
            'productoEliminandoId',
        );
        $this->original = [];
        $this->confirmandoDescartar = null;
    }

    /**
     * @return array<int, string>
     */
    private function imagenesPredeterminadas(): array
    {
        return collect(Catalogo::todos())
            ->map(fn (array $entrada, string $id): string => $id)
            ->sort()
            ->values()
            ->all();
    }

    private function guardarImagenProducto(TemporaryUploadedFile $archivo): string
    {
        return $this->guardarImagenOptimizada($archivo, 'productos', 'producto');
    }

    private function guardarImagenEnCatalogo(TemporaryUploadedFile $archivo): void
    {
        $info = @getimagesize($archivo->getRealPath());

        if ($info === false) {
            return;
        }

        $ancho = (int) $info[0];
        $alto = (int) $info[1];
        $lado = min($ancho, $alto);
        $objetivo = (int) config('productos.maestra_lado_objetivo', 800);
        $nombre = Catalogo::siguienteId();
        $maestra = Catalogo::rutaMaestraPara($nombre);

        $creada = $this->generarMaestraConRecorte(
            $archivo->getRealPath(),
            $maestra,
            (int) floor(($ancho - $lado) / 2),
            (int) floor(($alto - $lado) / 2),
            $lado,
            $objetivo,
        );

        if (! $creada) {
            return;
        }

        $tamanos = array_values(array_map(
            'intval',
            (array) config('productos.tamanos_predeterminadas', [256, 512, 800]),
        ));

        foreach ($tamanos as $tamano) {
            $this->generarDerivado($maestra, Catalogo::rutaDerivadoPara($tamano, $nombre), $tamano, 'webp');
        }

        $todos = Catalogo::todos();
        $todos[$nombre] = [
            'archivo' => $nombre,
            'sizes' => $tamanos,
            'legacy' => false,
        ];
        Catalogo::escribir($todos);
    }

    private function guardarImagenPredeterminada(string $id): string
    {
        $tamano = (int) config('productos.tamano_predeterminada_defecto', 512);
        $ruta = Catalogo::rutaDerivado($id, $tamano);

        if ($ruta === '' || ! File::exists($ruta)) {
            return '';
        }

        return $this->guardarImagenOptimizada($ruta, 'productos', 'producto');
    }

    private function eliminarImagenProducto(?string $ruta): void
    {
        if ($ruta === null || $ruta === '' || ImagenPublica::esPublica($ruta)) {
            return;
        }

        Storage::disk('public')->delete($ruta);
    }

    private function reglaUnicaNombre(?int $ignorarId = null): Unique
    {
        return Rule::unique('categorias', 'nombre')
            ->where('negocio_id', Auth::user()->negocio_id)
            ->ignore($ignorarId);
    }
}
