<?php

namespace App\Models;

use App\Support\CatalogoCache;
use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['negocio_id', 'nombre', 'activa', 'orden'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'orden' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $invalidar = static function (self $categoria): void {
            CatalogoCache::invalidar((int) $categoria->negocio_id);
        };

        static::saved($invalidar);
        static::deleted($invalidar);
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    /**
     * Scope the query to the categories that can be shown on the sales screen.
     */
    #[Scope]
    public function activas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }
}
