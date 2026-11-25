<?php

namespace App\Support;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Shared catalog cache for the point of sale.
 *
 * Models invalidate these keys through their saved/deleted events. Sales that
 * decrement stock bypass model events, so the checkout flow invalidates the
 * business explicitly after committing the transaction.
 */
final class CatalogoCache
{
    public const CATEGORIAS = 'categorias';

    public const PRODUCTOS = 'productos';

    public const PROMOCIONES = 'promociones';

    private const TTL_SEGUNDOS = 3600;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function recordar(int $negocioId, string $catalogo, callable $callback): mixed
    {
        return Cache::remember(
            self::clave($negocioId, $catalogo),
            self::TTL_SEGUNDOS,
            $callback,
        );
    }

    /**
     * Drop every catalog entry of the given business.
     */
    public static function invalidar(int $negocioId): void
    {
        foreach ([self::CATEGORIAS, self::PRODUCTOS, self::PROMOCIONES] as $catalogo) {
            Cache::forget(self::clave($negocioId, $catalogo));
        }
    }

    /**
     * @return Collection<int, Categoria>
     */
    public static function categorias(Negocio $negocio): Collection
    {
        return self::recordar(
            $negocio->id,
            self::CATEGORIAS,
            fn () => $negocio->categorias()
                ->activas()
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get(),
        );
    }

    /**
     * Unfiltered catalog, ordered by category and name.
     *
     * @return Collection<int, Producto>
     */
    public static function productos(Negocio $negocio): Collection
    {
        return self::recordar(
            $negocio->id,
            self::PRODUCTOS,
            fn () => Producto::query()
                ->with('categoria')
                ->whereHas('categoria', fn (Builder $query) => $query
                    ->where('negocio_id', $negocio->id)
                    ->where('activa', true))
                ->orderBy(
                    Categoria::query()
                        ->select('orden')
                        ->whereColumn('categorias.id', 'productos.categoria_id')
                )
                ->orderBy(
                    Categoria::query()
                        ->select('nombre')
                        ->whereColumn('categorias.id', 'productos.categoria_id')
                )
                ->orderBy('productos.nombre')
                ->get(),
        );
    }

    /**
     * Every promotion of the business, including the expired ones so they can
     * still be removed from the cart.
     *
     * @return Collection<int, Promocion>
     */
    public static function promociones(Negocio $negocio): Collection
    {
        return self::recordar(
            $negocio->id,
            self::PROMOCIONES,
            fn () => Promocion::query()
                ->with('productos.categoria')
                ->where('negocio_id', $negocio->id)
                ->orderBy('nombre')
                ->get(),
        );
    }

    /**
     * @param  Collection<int, Producto>  $productos
     * @return Collection<int, Producto>
     */
    public static function filtrarProductos(Collection $productos, ?int $categoriaId, string $busqueda): Collection
    {
        $busqueda = mb_strtolower(trim($busqueda));

        return $productos
            ->when(
                $categoriaId !== null,
                fn (Collection $coleccion) => $coleccion->filter(
                    fn (Producto $producto): bool => (int) $producto->categoria_id === $categoriaId
                )
            )
            ->when($busqueda !== '', function (Collection $coleccion) use ($busqueda): Collection {
                return $coleccion->filter(function (Producto $producto) use ($busqueda): bool {
                    if (str_contains(mb_strtolower($producto->nombre), $busqueda)) {
                        return true;
                    }

                    $categoria = $producto->categoria?->nombre;

                    return $categoria !== null && str_contains(mb_strtolower($categoria), $busqueda);
                });
            })
            ->values();
    }

    /**
     * @param  Collection<int, Promocion>  $promociones
     * @return Collection<int, Promocion>
     */
    public static function filtrarPromociones(Collection $promociones, ?int $categoriaId, string $busqueda): Collection
    {
        $busqueda = mb_strtolower(trim($busqueda));

        return $promociones
            ->when(
                $categoriaId !== null,
                fn (Collection $coleccion) => $coleccion->filter(
                    fn (Promocion $promocion): bool => $promocion->productos->contains(
                        fn (Producto $producto): bool => (int) $producto->categoria_id === $categoriaId
                    )
                )
            )
            ->when($busqueda !== '', function (Collection $coleccion) use ($busqueda): Collection {
                return $coleccion->filter(function (Promocion $promocion) use ($busqueda): bool {
                    if (str_contains(mb_strtolower($promocion->nombre), $busqueda)) {
                        return true;
                    }

                    return $promocion->productos->contains(
                        fn (Producto $producto): bool => str_contains(mb_strtolower($producto->nombre), $busqueda)
                    );
                });
            })
            ->values();
    }

    private static function clave(int $negocioId, string $catalogo): string
    {
        return 'catalogo:negocio:'.$negocioId.':'.$catalogo;
    }
}
