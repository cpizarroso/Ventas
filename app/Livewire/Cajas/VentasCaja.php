<?php

namespace App\Livewire\Cajas;

use App\Models\Caja;
use App\Models\Venta;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class VentasCaja extends Component
{
    use WithPagination;

    public int $cajaId;

    public ?int $ventaDetalleId = null;

    public function mount(int $cajaId): void
    {
        $this->cajaId = $cajaId;
    }

    public function verDetalle(int $ventaId): void
    {
        $this->cajaAutorizada();

        abort_if($this->consultarVenta($ventaId) === null, 404);

        $this->ventaDetalleId = $ventaId;
    }

    public function cerrarDetalle(): void
    {
        $this->ventaDetalleId = null;
    }

    public function render(): View
    {
        $caja = $this->cajaAutorizada();

        $ventas = $caja->ventas()
            ->with('user')
            ->ordenadasPorFechaYNumeroTicket()
            ->paginate(10);

        $ventaDetalle = $this->ventaDetalleId !== null
            ? $this->consultarVenta($this->ventaDetalleId)
            : null;

        return view('livewire.cajas.ventas-caja', [
            'ventas' => $ventas,
            'ventaDetalle' => $ventaDetalle,
        ]);
    }

    private function cajaAutorizada(): Caja
    {
        $caja = Caja::query()->findOrFail($this->cajaId);

        Gate::authorize('view', $caja);

        return $caja;
    }

    private function consultarVenta(int $ventaId): ?Venta
    {
        return Venta::query()
            ->whereKey($ventaId)
            ->where('caja_id', $this->cajaId)
            ->with([
                'user',
                'detalles' => fn ($consulta) => $consulta->orderBy('id'),
                'detalles.promocionItems' => fn ($consulta) => $consulta->orderBy('id'),
            ])
            ->first();
    }
}
