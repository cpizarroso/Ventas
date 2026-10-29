<?php

namespace App\Livewire\Configuracion;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class CategoriasAdmin extends Component
{
    use FlashesSavedStatus;

    public string $nombre = '';

    public ?int $editandoId = null;

    public string $nombreEdicion = '';

    public ?int $eliminandoId = null;

    public ?int $productosAbiertosId = null;

    public string $productoNombre = '';

    public string $productoPrecio = '';

    public string $productoStock = '';

    public ?int $productoEditandoId = null;

    public string $productoNombreEdicion = '';

    public string $productoPrecioEdicion = '';

    public string $productoStockEdicion = '';

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
        ], [
            'productoNombre.unique' => 'Ya existe un ítem con ese nombre en esta categoría.',
            'productoPrecio.decimal' => 'El precio admite como máximo dos decimales.',
            'productoStock.integer' => 'El stock debe ser un número entero.',
            'productoStock.min' => 'El stock no puede ser negativo.',
        ], [
            'productoNombre' => 'nombre',
            'productoPrecio' => 'precio',
            'productoStock' => 'stock',
        ]);

        $categoria->productos()->create([
            'nombre' => trim($this->productoNombre),
            'precio' => $this->productoPrecio,
            'stock' => $this->productoStock === '' ? null : (int) $this->productoStock,
        ]);

        $this->reset('productoNombre', 'productoPrecio', 'productoStock');

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
        $this->productoEliminandoId = null;
    }

    public function cancelarEdicionProducto(): void
    {
        $this->reset('productoEditandoId', 'productoNombreEdicion', 'productoPrecioEdicion', 'productoStockEdicion');
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
        ], [
            'productoNombreEdicion.unique' => 'Ya existe un ítem con ese nombre en esta categoría.',
            'productoPrecioEdicion.decimal' => 'El precio admite como máximo dos decimales.',
            'productoStockEdicion.integer' => 'El stock debe ser un número entero.',
        ], [
            'productoNombreEdicion' => 'nombre',
            'productoPrecioEdicion' => 'precio',
            'productoStockEdicion' => 'stock',
        ]);

        $producto->update([
            'nombre' => trim($this->productoNombreEdicion),
            'precio' => $this->productoPrecioEdicion,
            'stock' => $this->productoStockEdicion === '' ? null : (int) $this->productoStockEdicion,
        ]);

        $this->reset('productoEditandoId', 'productoNombreEdicion', 'productoPrecioEdicion', 'productoStockEdicion');

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
        $this->reset('productoEditandoId', 'productoNombreEdicion', 'productoPrecioEdicion', 'productoStockEdicion');
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

        return view('livewire.configuracion.categorias-admin', [
            'categorias' => $categorias,
            'productos' => $productos,
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
            'productoEditandoId',
            'productoNombreEdicion',
            'productoPrecioEdicion',
            'productoStockEdicion',
            'productoEliminandoId',
        );
    }

    private function reglaUnicaNombre(?int $ignorarId = null): Unique
    {
        return Rule::unique('categorias', 'nombre')
            ->where('negocio_id', Auth::user()->negocio_id)
            ->ignore($ignorarId);
    }
}
