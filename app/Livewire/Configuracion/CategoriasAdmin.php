<?php

namespace App\Livewire\Configuracion;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class CategoriasAdmin extends Component
{
    use FlashesSavedStatus, OptimizaImagenes, WithFileUploads;

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

    public bool $mostrarGaleriaPredeterminada = false;

    public ?int $productoEditandoId = null;

    public string $productoNombreEdicion = '';

    public string $productoPrecioEdicion = '';

    public string $productoStockEdicion = '';

    /** @var TemporaryUploadedFile|null */
    public $productoImagenEdicion = null;

    public ?string $productoImagenPredeterminadaEdicion = null;

    public bool $mostrarGaleriaPredeterminadaEdicion = false;

    public bool $quitarImagenEdicion = false;

    public ?int $productoEliminandoId = null;

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

        $this->reset('nombre');

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
    }

    public function cancelarEdicion(): void
    {
        $this->reset('editandoId', 'nombreEdicion');
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

        $this->reset('editandoId', 'nombreEdicion');

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
        $this->reset('editandoId', 'nombreEdicion');
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
        $this->reset('editandoId', 'nombreEdicion', 'eliminandoId');
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
            'productoNombre',
            'productoPrecio',
            'productoStock',
            'productoImagen',
            'productoImagenPredeterminada',
            'mostrarGaleriaPredeterminada',
        );

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
        $this->mostrarGaleriaPredeterminadaEdicion = false;
        $this->quitarImagenEdicion = false;
        $this->productoEliminandoId = null;
    }

    public function cancelarEdicionProducto(): void
    {
        $this->reset(
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoImagenEdicion',
            'productoImagenPredeterminadaEdicion',
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
        );
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
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
        );

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
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
        );
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

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;

        $categorias = $negocio
            ? $negocio->categorias()->withCount('productos')->orderBy('orden')->orderBy('nombre')->get()
            : collect();

        $productos = collect();

        if ($this->productosAbiertosId !== null) {
            $categoriaAbierta = $categorias->firstWhere('id', $this->productosAbiertosId);

            $productos = $categoriaAbierta
                ? $categoriaAbierta->productos()->orderBy('nombre')->get()
                : collect();
        }

        $urlBase = trim((string) config('productos.url_imagenes_predeterminadas'), '/');

        $imagenesPredeterminadas = array_map(
            fn (string $archivo): array => [
                'archivo' => $archivo,
                'url' => asset($urlBase.'/'.$archivo),
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
            'productoNombre',
            'productoPrecio',
            'productoStock',
            'productoImagen',
            'productoImagenPredeterminada',
            'mostrarGaleriaPredeterminada',
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoImagenEdicion',
            'productoImagenPredeterminadaEdicion',
            'mostrarGaleriaPredeterminadaEdicion',
            'quitarImagenEdicion',
            'productoEliminandoId',
        );
    }

    /**
     * @return array<int, string>
     */
    private function imagenesPredeterminadas(): array
    {
        $directorio = $this->directorioImagenesPredeterminadas();

        if (! File::isDirectory($directorio)) {
            return [];
        }

        return collect(File::files($directorio))
            ->filter(fn (\SplFileInfo $archivo): bool => in_array(strtolower($archivo->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->map(fn (\SplFileInfo $archivo): string => $archivo->getFilename())
            ->sort()
            ->values()
            ->all();
    }

    private function directorioImagenesPredeterminadas(): string
    {
        return (string) config('productos.imagenes_predeterminadas', public_path('img/productos-predeterminados'));
    }

    private function guardarImagenProducto(TemporaryUploadedFile $archivo): string
    {
        return $this->guardarImagenOptimizada($archivo, 'productos', 'producto');
    }

    private function guardarImagenPredeterminada(string $archivo): string
    {
        $ruta = $this->directorioImagenesPredeterminadas().DIRECTORY_SEPARATOR.$archivo;

        return $this->guardarImagenOptimizada($ruta, 'productos', 'producto');
    }

    private function eliminarImagenProducto(?string $ruta): void
    {
        if ($ruta !== null && $ruta !== '') {
            Storage::disk('public')->delete($ruta);
        }
    }

    private function reglaUnicaNombre(?int $ignorarId = null): Unique
    {
        return Rule::unique('categorias', 'nombre')
            ->where('negocio_id', Auth::user()->negocio_id)
            ->ignore($ignorarId);
    }
}
