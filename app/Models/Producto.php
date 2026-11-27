<?php

namespace App\Models;

use App\Support\CatalogoCache;
use App\Support\ImagenPublica;
use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['categoria_id', 'nombre', 'imagen', 'precio', 'stock', 'stock_min', 'activa'])]
class Producto extends Model
{
    /** @use HasFactory<ProductoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'stock_min' => 'integer',
            'activa' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $invalidar = static function (self $producto): void {
            $negocioId = $producto->categoria?->negocio_id;

            if ($negocioId !== null) {
                CatalogoCache::invalidar((int) $negocioId);
            }
        };

        static::saved($invalidar);
        static::deleted($invalidar);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Effective minimum: the product override, then the business default.
     */
    public function stockMinimo(): int
    {
        return $this->stock_min !== null
            ? max(0, (int) $this->stock_min)
            : $this->categoria?->negocio?->stockMinimo()
                ?? max(0, (int) config('productos.stock_min_defecto', 5));
    }

    /**
     * Scope the query to products that can be shown on the sales screen.
     */
    #[Scope]
    public function activas(Builder $query): Builder
    {
        return $query->where('productos.activa', true);
    }

    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function promociones(): BelongsToMany
    {
        return $this->belongsToMany(Promocion::class, 'promocion_productos')
            ->withPivot('cantidad');
    }

    /**
     * Get the public URL of the product image.
     */
    public function imagenUrl(): ?string
    {
        if ($this->imagen === null || $this->imagen === '') {
            return null;
        }

        return ImagenPublica::url($this->imagen);
    }
}
