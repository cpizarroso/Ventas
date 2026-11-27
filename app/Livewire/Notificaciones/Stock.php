<?php

namespace App\Livewire\Notificaciones;

use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Header bell with the low/zero stock alerts of the current business.
 *
 * A dismissed alert stays hidden until the stock or its threshold changes.
 */
class Stock extends Component
{
    #[On('stockActualizado')]
    public function actualizar(): void
    {
        // Recomputed on render.
    }

    /**
     * Dismiss one alert, remembering the values that were shown.
     */
    public function descartar(int $productoId): void
    {
        $producto = $this->productoDelNegocio($productoId);

        $this->guardarDescarte(
            $producto->id,
            (int) $producto->stock,
            $producto->stockMinimo(),
        );
    }

    /**
     * Dismiss every alert currently visible.
     */
    public function descartarTodo(): void
    {
        $alertas = $this->calcularAlertas();

        foreach ($alertas['agotados']->concat($alertas['bajos']) as $alerta) {
            $this->guardarDescarte(
                (int) $alerta['id'],
                (int) $alerta['stock'],
                (int) $alerta['min'],
            );
        }
    }

    public function render(): View
    {
        $alertas = $this->calcularAlertas();

        return view('livewire.notificaciones.stock', [
            'agotados' => $alertas['agotados'],
            'bajos' => $alertas['bajos'],
            'total' => $alertas['agotados']->count() + $alertas['bajos']->count(),
        ]);
    }

    /**
     * @return array{agotados: Collection<int, array{id: int, nombre: string, stock: int, min: int}>, bajos: Collection<int, array{id: int, nombre: string, stock: int, min: int}>}
     */
    private function calcularAlertas(): array
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return ['agotados' => collect(), 'bajos' => collect()];
        }

        $descartados = session('alertas_stock.descartados', []);

        $productos = Producto::query()
            ->whereHas('categoria', fn ($query) => $query->where('categorias.negocio_id', $negocio->id))
            ->with('categoria.negocio')
            ->get(['id', 'categoria_id', 'nombre', 'stock', 'stock_min']);

        $visible = function (Producto $producto) use ($descartados): bool {
            $min = $producto->stockMinimo();
            $descarte = $descartados[$producto->id] ?? null;

            return $descarte === null
                || (int) ($descarte['stock'] ?? PHP_INT_MIN) !== (int) $producto->stock
                || (int) ($descarte['min'] ?? PHP_INT_MIN) !== $min;
        };

        $formatear = fn (Producto $producto): array => [
            'id' => (int) $producto->id,
            'nombre' => $producto->nombre,
            'stock' => (int) $producto->stock,
            'min' => $producto->stockMinimo(),
        ];

        $agotados = $productos
            ->filter(fn (Producto $producto): bool => $producto->stock !== null && (int) $producto->stock <= 0)
            ->filter($visible)
            ->map($formatear)
            ->sortBy('nombre')
            ->values();

        $bajos = $productos
            ->filter(fn (Producto $producto): bool => $producto->stock !== null
                && (int) $producto->stock > 0
                && (int) $producto->stock <= $producto->stockMinimo())
            ->filter($visible)
            ->map($formatear)
            ->sortBy('nombre')
            ->values();

        return ['agotados' => $agotados, 'bajos' => $bajos];
    }

    private function guardarDescarte(int $productoId, int $stock, int $min): void
    {
        $descartados = session('alertas_stock.descartados', []);
        $descartados[$productoId] = ['stock' => $stock, 'min' => $min];

        session(['alertas_stock.descartados' => $descartados]);
    }

    private function productoDelNegocio(int $productoId): Producto
    {
        $user = Auth::user();

        abort_if($user?->negocio_id === null, 403, 'Tu usuario no tiene un negocio asignado.');

        $producto = Producto::query()
            ->whereKey($productoId)
            ->whereHas('categoria', fn ($query) => $query->where('categorias.negocio_id', $user->negocio_id))
            ->with('categoria.negocio')
            ->first();

        abort_if($producto === null, 404);

        return $producto;
    }
}
