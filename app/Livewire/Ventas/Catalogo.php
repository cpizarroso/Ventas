<?php

namespace App\Livewire\Ventas;

use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Promocion;
use App\Support\CatalogoCache;
use App\Support\DisponibilidadPromocion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Read-mostly half of the point of sale. It owns the catalog filters and only
 * asks the parent to mutate the sale, so typing or switching views never
 * re-renders the cart or the payment modal.
 */
class Catalogo extends Component
{
    public string $busqueda = '';

    public ?int $categoriaFiltroId = null;

    public string $vista = 'grid';

    /**
     * @var array<int, int>
     */
    public array $cantidadesPorProducto = [];

    /**
     * @var array<int, int>
     */
    public array $cantidadesPromocion = [];

    /**
     * Re-render after checkout or a stock change so the "last sale" badges and
     * stock labels stay in sync without reloading the page.
     */
    #[On('stockActualizado')]
    public function actualizarPorStock(): void
    {
        // The badges are read from the session on render.
    }

    /**
     * Receive the cart quantities directly: props passed by the parent are not
     * refreshed when the change originates from a child-dispatched event.
     *
     * @param  array<int, int>  $productos
     * @param  array<int, int>  $promociones
     */
    #[On('carritoActualizado')]
    public function sincronizarCarrito(array $productos, array $promociones): void
    {
        $this->cantidadesPorProducto = $productos;
        $this->cantidadesPromocion = $promociones;
    }

    /**
     * Filter the catalog by category; null means every category.
     */
    public function seleccionarCategoriaFiltro(?int $categoriaId): void
    {
        if ($categoriaId === null) {
            $this->categoriaFiltroId = null;

            return;
        }

        $negocio = Auth::user()?->negocio;
        abort_if($negocio === null, 404);

        $categoria = CatalogoCache::todo($negocio)[CatalogoCache::CATEGORIAS]->firstWhere('id', $categoriaId);
        abort_if($categoria === null, 404);

        $this->categoriaFiltroId = $categoria->id;
    }

    /**
     * Switch between grid and list layouts.
     */
    public function cambiarVista(string $vista): void
    {
        abort_unless(in_array($vista, ['grid', 'lista'], true), 404);

        $this->vista = $vista;
    }

    public function agregarProducto(int $productoId): void
    {
        $negocio = Auth::user()?->negocio;
        abort_if($negocio === null, 404);

        $disponible = CatalogoCache::todo($negocio)[CatalogoCache::PRODUCTOS]->contains(
            fn (Producto $producto): bool => $producto->id === $productoId
        );
        abort_unless($disponible, 404);

        $this->dispatch('agregarProducto', productoId: $productoId)->to(PantallaPrincipal::class);
    }

    public function agregarPromocion(int $promocionId): void
    {
        $negocio = Auth::user()?->negocio;
        abort_if($negocio === null, 404);

        $promocion = CatalogoCache::todo($negocio)[CatalogoCache::PROMOCIONES]->firstWhere('id', $promocionId);
        abort_if($promocion === null || ! DisponibilidadPromocion::estaDisponible($promocion), 404);

        $this->dispatch('agregarPromocion', promocionId: $promocionId)->to(PantallaPrincipal::class);
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            $categorias = collect();
            $productos = collect();
            $promociones = collect();
        } else {
            $catalogo = CatalogoCache::todo($negocio);
            $categorias = $catalogo[CatalogoCache::CATEGORIAS];
            $productos = CatalogoCache::filtrarProductos(
                $catalogo[CatalogoCache::PRODUCTOS],
                $this->categoriaFiltroId,
                $this->busqueda,
            );
            $promociones = CatalogoCache::filtrarPromociones(
                $catalogo[CatalogoCache::PROMOCIONES],
                $this->categoriaFiltroId,
                $this->busqueda,
            )->filter(fn (Promocion $promocion): bool => DisponibilidadPromocion::estaDisponible($promocion))
                ->values();
        }

        $ultimasUnidades = session('ultima_venta_unidades', []);

        return view('livewire.ventas.catalogo', [
            'negocio' => $negocio,
            'categorias' => $categorias,
            'productos' => $productos,
            'promociones' => $promociones,
            'cantidadesPorProducto' => $this->cantidadesPorProducto,
            'cantidadesPromocion' => $this->cantidadesPromocion,
            'ultimasUnidadesProductos' => is_array($ultimasUnidades) ? ($ultimasUnidades['productos'] ?? []) : [],
            'ultimasUnidadesPromociones' => is_array($ultimasUnidades) ? ($ultimasUnidades['promociones'] ?? []) : [],
            'puedeConfigurar' => Auth::user()?->puedeGestionarConfiguracion() ?? false,
        ]);
    }
}
