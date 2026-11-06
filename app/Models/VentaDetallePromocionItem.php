<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['venta_detalle_id', 'producto_id', 'nombre', 'cantidad'])]
class VentaDetallePromocionItem extends Model
{
    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
        ];
    }

    public function ventaDetalle(): BelongsTo
    {
        return $this->belongsTo(VentaDetalle::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
