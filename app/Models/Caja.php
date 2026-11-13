<?php

namespace App\Models;

use App\Enums\EstadoCaja;
use App\Enums\MetodoPago;
use Database\Factories\CajaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'negocio_id',
    'user_id',
    'cerrada_por_id',
    'estado',
    'monto_inicial',
    'observaciones_apertura',
    'fecha_apertura',
    'total_ventas',
    'total_efectivo',
    'total_qr',
    'cantidad_ventas',
    'monto_cierre',
    'monto_esperado_efectivo',
    'diferencia',
    'observaciones_cierre',
    'fecha_cierre',
])]
class Caja extends Model
{
    /** @use HasFactory<CajaFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estado' => EstadoCaja::class,
            'monto_inicial' => 'decimal:2',
            'fecha_apertura' => 'datetime',
            'total_ventas' => 'decimal:2',
            'total_efectivo' => 'decimal:2',
            'total_qr' => 'decimal:2',
            'cantidad_ventas' => 'integer',
            'monto_cierre' => 'decimal:2',
            'monto_esperado_efectivo' => 'decimal:2',
            'diferencia' => 'decimal:2',
            'fecha_cierre' => 'datetime',
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cerradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrada_por_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    /**
     * Scope to cash registers that are still open.
     *
     * @param  Builder<Caja>  $query
     */
    public function scopeAbiertas(Builder $query): void
    {
        $query->where('estado', EstadoCaja::Abierta);
    }

    public function estaAbierta(): bool
    {
        return $this->estado === EstadoCaja::Abierta;
    }

    public function montoEsperadoEfectivo(): float
    {
        if (! $this->estaAbierta() && $this->monto_esperado_efectivo !== null) {
            return (float) $this->monto_esperado_efectivo;
        }

        return (float) $this->monto_inicial + (float) $this->total_efectivo;
    }

    /**
     * Calculate the current totals of the sales linked to this cash register.
     *
     * @return array{cantidad: int, total: float, efectivo: float, qr: float}
     */
    public function resumenVentas(): array
    {
        $resumen = $this->ventas()
            ->selectRaw(
                'COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total,
                COALESCE(SUM(CASE WHEN metodo_pago = ? THEN total ELSE 0 END), 0) as efectivo,
                COALESCE(SUM(CASE WHEN metodo_pago = ? THEN total ELSE 0 END), 0) as qr',
                [MetodoPago::Efectivo->value, MetodoPago::Qr->value]
            )
            ->first();

        return [
            'cantidad' => (int) ($resumen->cantidad ?? 0),
            'total' => (float) ($resumen->total ?? 0),
            'efectivo' => (float) ($resumen->efectivo ?? 0),
            'qr' => (float) ($resumen->qr ?? 0),
        ];
    }
}
