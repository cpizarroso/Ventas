<?php

namespace App\Models;

use Database\Factories\VentaDetalleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['venta_id', 'producto_id', 'promocion_id', 'es_promocion', 'nombre', 'precio_unitario', 'cantidad', 'subtotal', 'glosa'])]
class VentaDetalle extends Model
{
    /** @use HasFactory<VentaDetalleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'es_promocion' => 'boolean',
            'precio_unitario' => 'decimal:2',
            'cantidad' => 'integer',
            'subtotal' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class);
    }

    public function promocionItems(): HasMany
    {
        return $this->hasMany(VentaDetallePromocionItem::class);
    }
}
