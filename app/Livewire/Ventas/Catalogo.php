<?php

namespace App\Livewire\Ventas;

use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Promocion;
use App\Support\CatalogoCache;
use App\Support\DisponibilidadPromocion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Read-mostly half of the point of sale. It owns the catalog filters and only
 * asks the parent to mutate the sale, so typing or switching views never
 * re-renders the cart or the payment modal.
 */
class Catalogo extends Component
{
    private const STOCK_BAJO = 5;

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

        $categoria = CatalogoCache::categorias($negocio)->firstWhere('id', $categoriaId);
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

        $disponible = CatalogoCache::productos($negocio)->contains(
            fn (Producto $producto): bool => $producto->id === $productoId
        );
        abort_unless($disponible, 404);

        $this->dispatch('agregarProducto', productoId: $productoId)->to(PantallaPrincipal::class);
    }

    public function agregarPromocion(int $promocionId): void
    {
        $negocio = Auth::user()?->negocio;
        abort_if($negocio === null, 404);

        $promocion = CatalogoCache::promociones($negocio)->firstWhere('id', $promocionId);
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
            $categorias = CatalogoCache::categorias($negocio);
            $productos = CatalogoCache::filtrarProductos(
                CatalogoCache::productos($negocio),
                $this->categoriaFiltroId,
                $this->busqueda,
            );
            $promociones = CatalogoCache::filtrarPromociones(
                CatalogoCache::promociones($negocio),
                $this->categoriaFiltroId,
                $this->busqueda,
            )->filter(fn (Promocion $promocion): bool => DisponibilidadPromocion::estaDisponible($promocion))
                ->values();
        }

        return view('livewire.ventas.catalogo', [
            'negocio' => $negocio,
            'categorias' => $categorias,
            'productos' => $productos,
            'promociones' => $promociones,
            'cantidadesPorProducto' => $this->cantidadesPorProducto,
            'cantidadesPromocion' => $this->cantidadesPromocion,
            'puedeConfigurar' => Auth::user()?->puedeGestionarConfiguracion() ?? false,
            'umbralStockBajo' => self::STOCK_BAJO,
        ]);
    }
}
