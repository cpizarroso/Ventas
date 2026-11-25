<?php

namespace App\Livewire\Ventas;

use App\Enums\EstadoCaja;
use App\Enums\MetodoPago;
use App\Models\Caja;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\Venta;
use App\Support\CatalogoCache;
use App\Support\DisponibilidadPromocion;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class PantallaPrincipal extends Component
{
    private const STOCK_BAJO = 5;

    private const MAX_CANTIDAD = 999;

    /**
     * Ítems del detalle de la venta, indexados por id de producto.
     *
     * @var array<int, int>
     */
    public array $carrito = [];

    /**
     * Promociones del detalle de la venta, indexadas por id de promoción.
     *
     * @var array<int, int>
     */
    public array $carritoPromociones = [];

    public bool $mostrarModalPago = false;

    public bool $confirmandoCancelarVenta = false;

    public ?int $ventaParaImprimirId = null;

    public ?int $ventaParaImprimirNumero = null;

    public ?string $metodoPago = null;

    public ?string $montoRecibido = null;

    public string $glosa = '';

    public string $cliente = '';

    public bool $paraLlevar = false;

    /**
     * Add a product to the sale detail.
     */
    #[On('agregarProducto')]
    public function agregarProducto(int $productoId): void
    {
        $producto = $this->productoDisponible($productoId);

        if (! $this->hayCajaParaVenta()) {
            return;
        }

        $this->carrito[$producto->id] = min(
            ($this->carrito[$producto->id] ?? 0) + 1,
            self::MAX_CANTIDAD
        );
    }

    /**
     * Increase the quantity of a product already in the sale detail.
     */
    public function incrementarCarrito(int $productoId): void
    {
        $producto = $this->productoDelCarrito($productoId);

        if (! $this->hayCajaParaVenta()) {
            return;
        }

        $this->carrito[$producto->id] = min(
            ($this->carrito[$producto->id] ?? 0) + 1,
            self::MAX_CANTIDAD
        );
    }

    /**
     * Decrease the quantity of a product in the sale detail.
     */
    public function disminuirCarrito(int $productoId): void
    {
        $producto = $this->productoDelCarrito($productoId);

        $cantidad = $this->carrito[$producto->id] ?? 0;

        if ($cantidad <= 1) {
            unset($this->carrito[$producto->id]);

            return;
        }

        $this->carrito[$producto->id] = $cantidad - 1;
    }

    /**
     * Remove a product from the sale detail.
     */
    public function quitarDelCarrito(int $productoId): void
    {
        $producto = $this->productoDelCarrito($productoId);

        unset($this->carrito[$producto->id]);
    }

    /**
     * Add an available promotion to the sale detail.
     */
    #[On('agregarPromocion')]
    public function agregarPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDisponible($promocionId);

        if (! $this->hayCajaParaVenta()) {
            return;
        }

        $this->carritoPromociones[$promocion->id] = min(
            ($this->carritoPromociones[$promocion->id] ?? 0) + 1,
            self::MAX_CANTIDAD
        );
    }

    /**
     * Increase the quantity of a promotion already in the sale detail.
     */
    public function incrementarPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        if (! $this->hayCajaParaVenta()) {
            return;
        }

        $this->carritoPromociones[$promocion->id] = min(
            ($this->carritoPromociones[$promocion->id] ?? 0) + 1,
            self::MAX_CANTIDAD
        );
    }

    /**
     * Decrease the quantity of a promotion in the sale detail.
     */
    public function disminuirPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        $cantidad = $this->carritoPromociones[$promocion->id] ?? 0;

        if ($cantidad <= 1) {
            unset($this->carritoPromociones[$promocion->id]);

            return;
        }

        $this->carritoPromociones[$promocion->id] = $cantidad - 1;
    }

    /**
     * Remove a promotion from the sale detail.
     */
    public function quitarPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        unset($this->carritoPromociones[$promocion->id]);
    }

    /**
     * Clear the whole sale detail.
     */
    public function vaciarCarrito(): void
    {
        $this->carrito = [];
        $this->carritoPromociones = [];
        $this->metodoPago = null;
        $this->montoRecibido = null;
        $this->glosa = '';
        $this->cliente = '';
        $this->paraLlevar = false;
        $this->mostrarModalPago = false;
        $this->confirmandoCancelarVenta = false;
        $this->resetErrorBag('carrito');
        $this->resetValidation(['metodoPago', 'montoRecibido', 'glosa', 'cliente']);
    }

    /**
     * Ask for confirmation before discarding an in-progress sale.
     */
    public function pedirCancelacion(): void
    {
        if (array_sum($this->carrito) + array_sum($this->carritoPromociones) <= 0) {
            return;
        }

        $this->confirmandoCancelarVenta = true;
    }

    public function cerrarCancelacion(): void
    {
        $this->confirmandoCancelarVenta = false;
    }

    /**
     * Discard the current sale detail and every unsaved field.
     */
    public function cancelarVenta(): void
    {
        $this->vaciarCarrito();
        $this->dispatch('toast', message: 'Venta cancelada');
    }

    /**
     * Open the payment modal when there is something to charge.
     */
    public function abrirModalPago(): void
    {
        if ($this->cajaAbierta() === null) {
            $this->addError('carrito', 'Debes abrir tu caja antes de cobrar.');

            return;
        }

        if (
            $this->lineasDesde($this->carrito)->isEmpty()
            && $this->lineasPromocionDesde($this->carritoPromociones)->isEmpty()
        ) {
            $this->addError('carrito', 'Agrega al menos un ítem antes de pagar.');

            return;
        }

        $negocio = Auth::user()?->negocio;
        $metodoPredeterminado = $negocio?->metodoPagoPredeterminado() ?? MetodoPago::Efectivo;

        $this->mostrarModalPago = true;
        $this->metodoPago = $metodoPredeterminado->value;
        $this->montoRecibido = $metodoPredeterminado === MetodoPago::Efectivo ? '' : null;
        $this->resetValidation(['metodoPago', 'montoRecibido', 'glosa', 'cliente']);
    }

    public function cerrarModalPago(): void
    {
        $this->mostrarModalPago = false;
    }

    /**
     * Select how the sale will be paid.
     */
    public function seleccionarMetodoPago(string $metodo): void
    {
        $metodoPago = MetodoPago::tryFrom($metodo);

        abort_if($metodoPago === null, 404);

        $this->metodoPago = $metodoPago->value;

        if ($metodoPago === MetodoPago::Qr) {
            $this->montoRecibido = null;
        }

        $this->resetValidation(['metodoPago', 'montoRecibido']);
    }

    /**
     * Persist the sale detail as a single sale and clear it.
     */
    public function cobrar(): void
    {
        $user = Auth::user();
        $negocio = $user?->negocio;

        abort_if($negocio === null, 404);

        $cajaAbierta = $this->cajaAbierta();

        if ($cajaAbierta === null) {
            $this->addError('carrito', 'Debes abrir tu caja antes de cobrar.');

            return;
        }

        $lineas = $this->lineasDesde($this->carrito);
        $lineasPromociones = $this->lineasPromocionDesde($this->carritoPromociones);

        $idsEnCarrito = collect($this->carritoPromociones)
            ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
            ->keys()
            ->map(fn ($id) => (int) $id);
        $idsEnLineas = $lineasPromociones
            ->pluck('promocion')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($idsEnCarrito->diff($idsEnLineas)->isNotEmpty()) {
            $this->carritoPromociones = $lineasPromociones
                ->mapWithKeys(fn (array $linea) => [$linea['promocion']->id => $linea['cantidad']])
                ->all();
            $this->addError('carrito', 'Una o más promociones ya no están disponibles. Se quitaron del detalle.');

            return;
        }

        if ($lineasPromociones->contains(fn (array $linea) => ! $linea['disponible'])) {
            $this->carritoPromociones = $lineasPromociones
                ->filter(fn (array $linea) => $linea['disponible'])
                ->mapWithKeys(fn (array $linea) => [$linea['promocion']->id => $linea['cantidad']])
                ->all();
            $this->addError('carrito', 'Una o más promociones ya no están vigentes. Se quitaron del detalle.');

            return;
        }

        if ($lineas->isEmpty() && $lineasPromociones->isEmpty()) {
            $this->addError('carrito', 'Agrega al menos un ítem antes de cobrar.');

            return;
        }

        $totalEnCentavos = $lineas->sum('subtotal_centavos')
            + $lineasPromociones->sum('subtotal_centavos');

        $this->montoRecibido = $this->montoRecibido === null
            ? null
            : str_replace(',', '.', trim($this->montoRecibido));

        $this->glosa = trim($this->glosa);
        $this->cliente = trim($this->cliente);

        $this->validate([
            'metodoPago' => ['required', Rule::enum(MetodoPago::class)],
            'glosa' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:120'],
        ]);

        $metodoPago = MetodoPago::from($this->metodoPago);
        $glosa = $this->glosa === '' ? null : $this->glosa;
        $cliente = $this->cliente === '' ? null : $this->cliente;
        $paraLlevar = $this->paraLlevar;
        $montoRecibidoEnCentavos = $totalEnCentavos;
        $cambioEnCentavos = 0;

        if ($metodoPago === MetodoPago::Efectivo) {
            $this->validate([
                'montoRecibido' => ['required', 'numeric', 'min:0', 'max:9999999'],
            ]);

            $montoRecibidoEnCentavos = $this->centavosDesde($this->montoRecibido);

            if ($montoRecibidoEnCentavos < $totalEnCentavos) {
                $this->addError('montoRecibido', 'El efectivo recibido no cubre el total a cobrar.');

                return;
            }

            $cambioEnCentavos = $montoRecibidoEnCentavos - $totalEnCentavos;
        }

        $advertenciasStock = [];
        $requerimientosStock = $this->requerimientosStock($lineas, $lineasPromociones)->sortKeys();

        $venta = DB::transaction(function () use ($negocio, $cajaAbierta, $user, $lineas, $lineasPromociones, $requerimientosStock, $totalEnCentavos, $metodoPago, $montoRecibidoEnCentavos, $cambioEnCentavos, $glosa, $cliente, $paraLlevar, &$advertenciasStock) {
            $negocio = Negocio::query()->whereKey($negocio->id)->lockForUpdate()->firstOrFail();

            $caja = Caja::query()
                ->whereKey($cajaAbierta->id)
                ->where('estado', EstadoCaja::Abierta)
                ->lockForUpdate()
                ->first();

            if ($caja === null) {
                throw ValidationException::withMessages([
                    'carrito' => 'Tu caja se cerró antes de completar el cobro. Ábrela nuevamente.',
                ]);
            }

            $fechaTicket = now()->toDateString();
            $ultimoNumero = Venta::query()
                ->where('negocio_id', $negocio->id)
                ->where('fecha_ticket', $fechaTicket)
                ->max('numero_ticket');

            $venta = $negocio->ventas()->create([
                'caja_id' => $caja->id,
                'user_id' => $user->id,
                'numero_ticket' => ((int) $ultimoNumero) + 1,
                'fecha_ticket' => $fechaTicket,
                'total' => $totalEnCentavos / 100,
                'metodo_pago' => $metodoPago,
                'monto_recibido' => $montoRecibidoEnCentavos / 100,
                'cambio' => $cambioEnCentavos / 100,
                'glosa' => $glosa,
                'cliente' => $cliente,
                'para_llevar' => $paraLlevar,
            ]);

            $caja->increment('cantidad_ventas');
            $caja->increment('total_ventas', $totalEnCentavos / 100);
            $caja->increment(
                $metodoPago === MetodoPago::Efectivo ? 'total_efectivo' : 'total_qr',
                $totalEnCentavos / 100
            );

            if ($lineas->isNotEmpty()) {
                $venta->detalles()->createMany(
                    $lineas->map(fn (array $linea) => [
                        'producto_id' => $linea['producto']->id,
                        'promocion_id' => null,
                        'es_promocion' => false,
                        'nombre' => $linea['producto']->nombre,
                        'precio_unitario' => $linea['precio_unitario'],
                        'cantidad' => $linea['cantidad'],
                        'subtotal' => $linea['subtotal'],
                    ])->all()
                );
            }

            foreach ($lineasPromociones as $linea) {
                $detalle = $venta->detalles()->create([
                    'producto_id' => null,
                    'promocion_id' => $linea['promocion']->id,
                    'es_promocion' => true,
                    'nombre' => $linea['promocion']->nombre,
                    'precio_unitario' => $linea['precio_unitario'],
                    'cantidad' => $linea['cantidad'],
                    'subtotal' => $linea['subtotal'],
                ]);

                $detalle->promocionItems()->createMany(
                    $linea['componentes']->map(fn (array $componente) => [
                        'producto_id' => $componente['producto']->id,
                        'nombre' => $componente['producto']->nombre,
                        'cantidad' => $componente['cantidad'],
                    ])->all()
                );
            }

            foreach ($requerimientosStock as $requerimiento) {
                $producto = Producto::query()
                    ->whereKey($requerimiento['producto']->id)
                    ->lockForUpdate()
                    ->first();

                if ($producto === null || $producto->stock === null) {
                    continue;
                }

                $nuevoStock = $producto->stock - $requerimiento['cantidad'];

                $producto->decrement('stock', $requerimiento['cantidad']);

                if ($nuevoStock < 0) {
                    $advertenciasStock[] = $producto->nombre.' ('.$nuevoStock.')';
                }
            }

            return $venta;
        });

        // Los decrementos de stock usan query builder y no disparan eventos de modelo.
        CatalogoCache::invalidar($negocio->id);

        $this->carrito = [];
        $this->carritoPromociones = [];
        $this->mostrarModalPago = false;
        $this->metodoPago = null;
        $this->montoRecibido = null;
        $this->glosa = '';
        $this->cliente = '';
        $this->paraLlevar = false;
        $this->ventaParaImprimirId = $venta->id;
        $this->ventaParaImprimirNumero = $venta->numeroTicket();
        $this->resetErrorBag('carrito');
        $this->resetValidation(['metodoPago', 'montoRecibido', 'glosa', 'cliente']);

        $mensaje = 'Venta #'.$venta->numeroTicket().' registrada por Bs '.number_format($totalEnCentavos / 100, 2)
            .' ('.$metodoPago->etiqueta().').';

        if ($metodoPago === MetodoPago::Efectivo) {
            $mensaje .= ' Recibido: Bs '.number_format($montoRecibidoEnCentavos / 100, 2)
                .' · Cambio: Bs '.number_format($cambioEnCentavos / 100, 2).'.';
        }

        if ($advertenciasStock !== []) {
            $mensaje .= ' Atención: stock negativo en '.implode(', ', $advertenciasStock).'.';
        }

        session()->flash('status', $mensaje);
    }

    /**
     * Dismiss the optional print prompt without printing the receipt.
     */
    public function omitirImpresion(): void
    {
        $this->ventaParaImprimirId = null;
        $this->ventaParaImprimirNumero = null;
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;
        $cajaAbierta = $this->cajaAbierta();

        // Only the cart is needed here; the catalog component loads and filters
        // its own data from the shared cache.
        $cantidadesPorProducto = collect($this->carrito)
            ->map(fn ($cantidad): int => is_numeric($cantidad)
                ? max(0, min((int) $cantidad, self::MAX_CANTIDAD))
                : 0)
            ->all();

        $cantidadesPromocion = collect($this->carritoPromociones)
            ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
            ->map(fn ($cantidad): int => min((int) $cantidad, self::MAX_CANTIDAD))
            ->all();

        $lineasCarrito = $this->lineasDesde($this->carrito);
        $lineasPromociones = $this->lineasPromocionDesde($this->carritoPromociones);
        $totalCarritoEnCentavos = $lineasCarrito->sum('subtotal_centavos')
            + $lineasPromociones->sum('subtotal_centavos');
        $hayAdvertenciasStock = $this->hayAdvertenciasStock($lineasCarrito, $lineasPromociones);

        $montoRecibidoEnCentavos = $this->montoRecibido !== null && $this->montoRecibido !== ''
            ? $this->centavosDesde($this->montoRecibido)
            : null;

        $cambioEnCentavos = $montoRecibidoEnCentavos !== null
            ? max(0, $montoRecibidoEnCentavos - $totalCarritoEnCentavos)
            : 0;

        $efectivoSuficiente = $montoRecibidoEnCentavos !== null
            && $montoRecibidoEnCentavos >= $totalCarritoEnCentavos;

        $puedeCobrar = $cajaAbierta !== null
            && $totalCarritoEnCentavos > 0
            && $this->metodoPago !== null
            && ($this->metodoPago !== MetodoPago::Efectivo->value || $efectivoSuficiente);

        return view('livewire.ventas.pantalla-principal', [
            'negocio' => $negocio,
            'cajaAbierta' => $cajaAbierta,
            'cantidadesPorProducto' => $cantidadesPorProducto,
            'cantidadesPromocion' => $cantidadesPromocion,
            'lineasCarrito' => $lineasCarrito,
            'lineasPromociones' => $lineasPromociones,
            'totalCarrito' => number_format($totalCarritoEnCentavos / 100, 2),
            'cantidadItems' => $lineasCarrito->sum('cantidad') + $lineasPromociones->sum('cantidad'),
            'cambio' => number_format($cambioEnCentavos / 100, 2),
            'efectivoSuficiente' => $efectivoSuficiente,
            'puedeCobrar' => $puedeCobrar,
            'hayAdvertenciasStock' => $hayAdvertenciasStock,
        ]);
    }

    /**
     * Base query for promotions active today with at least one sellable item.
     *
     * @return Builder<Promocion>
     */
    private function promocionesVigentesQuery(): Builder
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return Promocion::query()->whereRaw('1 = 0');
        }

        return Promocion::query()
            ->with('productos.categoria')
            ->where('negocio_id', $negocio->id)
            ->vigentes()
            ->whereHas('productos')
            ->whereDoesntHave(
                'productos.categoria',
                fn (Builder $query) => $query->where('activa', false)
            );
    }

    /**
     * Load, in a single query, every promotion the POS needs: the ones available
     * today and the ones currently in the cart (even if they are no longer valid).
     *
     * @return Collection<int, Promocion>
     */
    private function promocionesCargadas(): Collection
    {
        $negocio = Auth::user()?->negocio;

        // La vigencia se resuelve en memoria para que las promociones vencidas
        // sigan visibles y se puedan quitar del carrito.
        return $negocio === null ? collect() : CatalogoCache::promociones($negocio);
    }

    /**
     * Scope a promotion lookup to an available promotion of the authenticated business.
     */
    private function promocionDisponible(int $promocionId): Promocion
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 404);

        $promocion = $this->promocionesVigentesQuery()
            ->whereKey($promocionId)
            ->first();

        abort_if($promocion === null, 404);

        return $promocion;
    }

    /**
     * Scope a promotion lookup to the authenticated business, regardless of availability.
     */
    private function promocionDelCarrito(int $promocionId): Promocion
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 404);

        $promocion = Promocion::query()
            ->with('productos.categoria')
            ->where('negocio_id', $negocio->id)
            ->whereKey($promocionId)
            ->first();

        abort_if($promocion === null, 404);

        return $promocion;
    }

    /**
     * Resolve promotion lines from the database, never from client-provided prices.
     *
     * @param  array<int|string, mixed>  $cantidades
     * @param  Collection<int, Promocion>|null  $promociones
     * @return Collection<int, array{promocion: Promocion, cantidad: int, precio_unitario: float, subtotal: float, subtotal_centavos: int, componentes: Collection<int, array{producto: Producto, cantidad: int}>, disponible: bool}>
     */
    private function lineasPromocionDesde(array $cantidades, ?Collection $promociones = null): Collection
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return collect();
        }

        $normalizadas = collect($cantidades)
            ->map(fn ($cantidad) => is_numeric($cantidad) ? (int) $cantidad : 0)
            ->filter(fn (int $cantidad) => $cantidad > 0)
            ->map(fn (int $cantidad) => min($cantidad, self::MAX_CANTIDAD));

        if ($normalizadas->isEmpty()) {
            return collect();
        }

        $promociones ??= $this->promocionesDelCarrito();

        return $promociones
            ->whereIn('id', $normalizadas->keys()->all())
            ->sortBy('nombre')
            ->map(function (Promocion $promocion) use ($normalizadas): array {
                $cantidad = $normalizadas[$promocion->id];
                $precioEnCentavos = (int) round((float) $promocion->precio * 100);

                return [
                    'promocion' => $promocion,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioEnCentavos / 100,
                    'subtotal' => $precioEnCentavos * $cantidad / 100,
                    'subtotal_centavos' => $precioEnCentavos * $cantidad,
                    'componentes' => $promocion->productos
                        ->map(fn (Producto $producto): array => [
                            'producto' => $producto,
                            'cantidad' => (int) $producto->pivot->cantidad,
                        ])
                        ->values(),
                    'disponible' => $this->promocionEstaDisponible($promocion),
                ];
            })
            ->values();
    }

    /**
     * Promotions currently in the cart, including ones that stopped being available.
     *
     * @return Collection<int, Promocion>
     */
    private function promocionesDelCarrito(): Collection
    {
        $ids = collect($this->carritoPromociones)
            ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids === []) {
            return collect();
        }

        return $this->promocionesCargadas()
            ->whereIn('id', $ids)
            ->keyBy('id');
    }

    private function promocionEstaDisponible(Promocion $promocion): bool
    {
        return DisponibilidadPromocion::estaDisponible($promocion);
    }

    /**
     * Aggregate stock requirements across normal lines and promotion components.
     *
     * @param  Collection<int, array{producto: Producto, cantidad: int}>  $lineas
     * @param  Collection<int, array{componentes: Collection<int, array{producto: Producto, cantidad: int}>, cantidad: int}>  $lineasPromociones
     * @return Collection<int, array{producto: Producto, cantidad: int}>
     */
    private function requerimientosStock(Collection $lineas, Collection $lineasPromociones): Collection
    {
        $requerimientos = collect();

        foreach ($lineas as $linea) {
            $producto = $linea['producto'];
            $actual = $requerimientos->get($producto->id, ['producto' => $producto, 'cantidad' => 0]);
            $actual['cantidad'] += $linea['cantidad'];
            $requerimientos->put($producto->id, $actual);
        }

        foreach ($lineasPromociones as $linea) {
            foreach ($linea['componentes'] as $componente) {
                $producto = $componente['producto'];
                $actual = $requerimientos->get($producto->id, ['producto' => $producto, 'cantidad' => 0]);
                $actual['cantidad'] += $componente['cantidad'] * $linea['cantidad'];
                $requerimientos->put($producto->id, $actual);
            }
        }

        return $requerimientos;
    }

    private function hayAdvertenciasStock(Collection $lineas, Collection $lineasPromociones): bool
    {
        return $this->requerimientosStock($lineas, $lineasPromociones)
            ->contains(fn (array $requerimiento) => $requerimiento['producto']->stock !== null
                && $requerimiento['cantidad'] > $requerimiento['producto']->stock);
    }

    /**
     * Scope a product lookup to an available product of the authenticated business.
     */
    private function productoDisponible(int $productoId): Producto
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 404);

        $producto = Producto::query()
            ->whereKey($productoId)
            ->whereHas('categoria', fn (Builder $query) => $query
                ->where('negocio_id', $negocio->id)
                ->where('activa', true))
            ->first();

        abort_if($producto === null, 404);

        return $producto;
    }

    /**
     * Scope a product lookup to the authenticated business.
     */
    private function productoDelCarrito(int $productoId): Producto
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 404);

        $producto = Producto::query()
            ->whereKey($productoId)
            ->whereHas('categoria', fn (Builder $query) => $query->where('negocio_id', $negocio->id))
            ->first();

        abort_if($producto === null, 404);

        return $producto;
    }

    /**
     * Resolve cart lines from the database, never from client-provided prices.
     *
     * @param  array<int|string, mixed>  $cantidades
     * @return Collection<int, array{producto: Producto, cantidad: int, precio_unitario: float, subtotal: float, subtotal_centavos: int}>
     */
    private function lineasDesde(array $cantidades): Collection
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return collect();
        }

        $normalizadas = collect($cantidades)
            ->map(fn ($cantidad) => is_numeric($cantidad) ? (int) $cantidad : 0)
            ->filter(fn (int $cantidad) => $cantidad > 0)
            ->map(fn (int $cantidad) => min($cantidad, self::MAX_CANTIDAD));

        if ($normalizadas->isEmpty()) {
            return collect();
        }

        return Producto::query()
            ->with('categoria')
            ->whereIn('id', $normalizadas->keys())
            ->whereHas('categoria', fn (Builder $subquery) => $subquery->where('negocio_id', $negocio->id))
            ->orderBy('nombre')
            ->get()
            ->map(function (Producto $producto) use ($normalizadas) {
                $cantidad = $normalizadas[$producto->id];
                $precioEnCentavos = $this->precioEnCentavos($producto);

                return [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioEnCentavos / 100,
                    'subtotal' => $precioEnCentavos * $cantidad / 100,
                    'subtotal_centavos' => $precioEnCentavos * $cantidad,
                ];
            });
    }

    private function hayCajaParaVenta(): bool
    {
        if ($this->cajaAbierta() !== null) {
            return true;
        }

        $this->dispatch(
            'toast',
            message: 'Abre tu caja antes de agregar ítems a la venta.',
            type: 'warning',
        );

        return false;
    }

    private function cajaAbierta(): ?Caja
    {
        return once(function (): ?Caja {
            $user = Auth::user();

            if ($user === null || $user->negocio_id === null) {
                return null;
            }

            return Caja::query()
                ->where('negocio_id', $user->negocio_id)
                ->where('user_id', $user->id)
                ->abiertas()
                ->latest('fecha_apertura')
                ->first();
        });
    }

    private function precioEnCentavos(Producto $producto): int
    {
        return (int) round((float) $producto->precio * 100);
    }

    private function centavosDesde(?string $monto): int
    {
        return (int) round((float) str_replace(',', '.', (string) $monto) * 100);
    }
}
