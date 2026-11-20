<?php

namespace App\Models;

use App\Enums\MetodoPago;
use Database\Factories\VentaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['negocio_id', 'caja_id', 'user_id', 'numero_ticket', 'fecha_ticket', 'total', 'metodo_pago', 'monto_recibido', 'cambio', 'glosa', 'cliente', 'para_llevar'])]
class Venta extends Model
{
    /** @use HasFactory<VentaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'metodo_pago' => MetodoPago::class,
            'monto_recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
            'para_llevar' => 'boolean',
            'fecha_ticket' => 'date',
            'numero_ticket' => 'integer',
        ];
    }

    /**
     * Order sales by date, then by the displayed ticket number and id.
     *
     * @param  Builder<Venta>  $query
     */
    public function scopeOrdenadasPorFechaYNumeroTicket(Builder $query): void
    {
        $query->orderByRaw('COALESCE(ventas.fecha_ticket, DATE(ventas.created_at)) ASC')
            ->orderByRaw('COALESCE(ventas.numero_ticket, ventas.id) ASC')
            ->orderBy('ventas.id');
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    /**
     * Classify the sale by its normal and promotional lines.
     */
    public function tipoReporte(): string
    {
        $tienePromocion = $this->detalles->contains(
            fn (VentaDetalle $detalle) => $detalle->es_promocion
        );
        $tieneNormal = $this->detalles->contains(
            fn (VentaDetalle $detalle) => ! $detalle->es_promocion
        );

        if ($tienePromocion && $tieneNormal) {
            return 'mixta';
        }

        return $tienePromocion ? 'promocion' : 'normal';
    }

    public function etiquetaTipoReporte(): string
    {
        return match ($this->tipoReporte()) {
            'promocion' => 'Promoción',
            'mixta' => 'Mixta',
            default => 'Normal',
        };
    }

    public function promocionesNombres(): string
    {
        return $this->detalles
            ->where('es_promocion', true)
            ->pluck('nombre')
            ->unique()
            ->implode(', ');
    }

    public function numeroTicket(): int
    {
        return (int) ($this->numero_ticket ?? $this->id);
    }
}
