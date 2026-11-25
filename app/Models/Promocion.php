<?php

namespace App\Models;

use App\Support\CatalogoCache;
use Carbon\CarbonInterface;
use Database\Factories\PromocionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'negocio_id',
    'nombre',
    'descripcion',
    'precio',
    'fecha_inicio',
    'fecha_fin',
    'dias_semana',
    'activa',
])]
class Promocion extends Model
{
    /** @use HasFactory<PromocionFactory> */
    use HasFactory;

    protected $table = 'promociones';

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'dias_semana' => 'array',
            'activa' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $invalidar = static function (self $promocion): void {
            CatalogoCache::invalidar((int) $promocion->negocio_id);
        };

        static::saved($invalidar);
        static::deleted($invalidar);
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'promocion_productos')
            ->withPivot('cantidad');
    }

    public function ventaDetalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    /**
     * Scope the query to promotions available on the given date.
     */
    #[Scope]
    public function vigentes(Builder $query, ?CarbonInterface $fecha = null): Builder
    {
        $fecha ??= now();

        return $query
            ->where('activa', true)
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->whereDate('fecha_fin', '>=', $fecha)
            ->whereJsonContains('dias_semana', $fecha->isoWeekday());
    }
}
