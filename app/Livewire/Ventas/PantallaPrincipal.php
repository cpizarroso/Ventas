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
    public function agregarProducto(int $productoId, int $paso = 1): void
    {
        $producto = $this->productoDisponible($productoId);
        $paso = max(1, min($paso, self::MAX_CANTIDAD));

        $this->aplicarCantidadProducto(
            $producto->id,
            (int) ($this->carrito[$producto->id] ?? 0) + $paso,
        );
    }

    /**
     * Increase the quantity of a product already in the sale detail.
     */
    public function incrementarCarrito(int $productoId, int $paso = 1): void
    {
        $producto = $this->productoDelCarrito($productoId);
        $paso = max(1, min($paso, self::MAX_CANTIDAD));

        $this->aplicarCantidadProducto(
            $producto->id,
            (int) ($this->carrito[$producto->id] ?? 0) + $paso,
        );
    }

    /**
     * Set an exact quantity from the numeric input (one request on change).
     */
    public function fijarCantidad(int $productoId, mixed $cantidad): void
    {
        $producto = $this->productoDelCarrito($productoId);

        if (! is_numeric($cantidad)) {
            return;
        }

        $this->aplicarCantidadProducto($producto->id, (int) $cantidad);
    }

    /**
     * Decrease the quantity of a product in the sale detail.
     */
    public function disminuirCarrito(int $productoId): void
    {
        $producto = $this->productoDelCarrito($productoId);

        $cantidad = (int) ($this->carrito[$producto->id] ?? 0);

        $this->aplicarCantidadProducto($producto->id, $cantidad - 1, false);
    }

    /**
     * Remove a product from the sale detail.
     */
    public function quitarDelCarrito(int $productoId): void
    {
        $producto = $this->productoDelCarrito($productoId);

        unset($this->carrito[$producto->id]);
        $this->sincronizarCarritoConCatalogo();
    }

    /**
     * Apply a product quantity, keeping the "open cash drawer to increase" rule.
     * When overselling is disabled, an invalid quantity is reverted with a toast.
     */
    private function aplicarCantidadProducto(int $productoId, int $cantidad, bool $validarCaja = true): void
    {
        $producto = $this->productoDelCarrito($productoId);

        try {
            $nueva = max(0, min($cantidad, self::MAX_CANTIDAD));
            $anterior = (int) ($this->carrito[$producto->id] ?? 0);

            if ($nueva > $anterior && $validarCaja && ! $this->hayCajaParaVenta()) {
                return;
            }

            if ($nueva === 0) {
                unset($this->carrito[$producto->id]);

                return;
            }

            $controlarStock = ! $this->negocioPermiteStockNegativo();
            $advertenciaAntes = $controlarStock && $this->hayAdvertenciasStock(
                $this->lineasDesde($this->carrito),
                $this->lineasPromocionDesde($this->carritoPromociones),
            );

            $this->carrito[$producto->id] = $nueva;

            // Only block the transition into an invalid cart, so an already invalid
            // one can still be corrected by lowering quantities.
            if ($controlarStock && ! $advertenciaAntes) {
                $ahora = $this->hayAdvertenciasStock(
                    $this->lineasDesde($this->carrito),
                    $this->lineasPromocionDesde($this->carritoPromociones),
                );

                if ($ahora) {
                    if ($anterior === 0) {
                        unset($this->carrito[$producto->id]);
                    } else {
                        $this->carrito[$producto->id] = $anterior;
                    }

                    $this->dispatch(
                        'toast',
                        message: 'Stock insuficiente. Ajusta la cantidad o activa la venta con stock negativo en Configuración.',
                        type: 'warning',
                    );
                }
            }
        } finally {
            $this->sincronizarCarritoConCatalogo();
        }
    }

    /**
     * Add an available promotion to the sale detail.
     */
    #[On('agregarPromocion')]
    public function agregarPromocion(int $promocionId, int $paso = 1): void
    {
        $promocion = $this->promocionDisponible($promocionId);
        $paso = max(1, min($paso, self::MAX_CANTIDAD));

        $this->aplicarCantidadPromocion(
            $promocion->id,
            (int) ($this->carritoPromociones[$promocion->id] ?? 0) + $paso,
        );
    }

    /**
     * Increase the quantity of a promotion already in the sale detail.
     */
    public function incrementarPromocion(int $promocionId, int $paso = 1): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);
        $paso = max(1, min($paso, self::MAX_CANTIDAD));

        $this->aplicarCantidadPromocion(
            $promocion->id,
            (int) ($this->carritoPromociones[$promocion->id] ?? 0) + $paso,
        );
    }

    /**
     * Set an exact promotion quantity from the numeric input.
     */
    public function fijarCantidadPromocion(int $promocionId, mixed $cantidad): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        if (! is_numeric($cantidad)) {
            return;
        }

        $this->aplicarCantidadPromocion($promocion->id, (int) $cantidad);
    }

    /**
     * Decrease the quantity of a promotion in the sale detail.
     */
    public function disminuirPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        $cantidad = (int) ($this->carritoPromociones[$promocion->id] ?? 0);

        $this->aplicarCantidadPromocion($promocion->id, $cantidad - 1, false);
    }

    /**
     * Apply a promotion quantity, reverting it when it would oversell and the
     * business has not explicitly allowed negative stock.
     */
    private function aplicarCantidadPromocion(int $promocionId, int $cantidad, bool $validarCaja = true): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        try {
            $nueva = max(0, min($cantidad, self::MAX_CANTIDAD));
            $anterior = (int) ($this->carritoPromociones[$promocion->id] ?? 0);

            if ($nueva > $anterior && $validarCaja && ! $this->hayCajaParaVenta()) {
                return;
            }

            if ($nueva === 0) {
                unset($this->carritoPromociones[$promocion->id]);

                return;
            }

            $controlarStock = ! $this->negocioPermiteStockNegativo();
            $advertenciaAntes = $controlarStock && $this->hayAdvertenciasStock(
                $this->lineasDesde($this->carrito),
                $this->lineasPromocionDesde($this->carritoPromociones),
            );

            $this->carritoPromociones[$promocion->id] = $nueva;

            // Only block the transition into an invalid cart, so an already invalid
            // one can still be corrected by lowering quantities.
            if ($controlarStock && ! $advertenciaAntes) {
                $ahora = $this->hayAdvertenciasStock(
                    $this->lineasDesde($this->carrito),
                    $this->lineasPromocionDesde($this->carritoPromociones),
                );

                if ($ahora) {
                    if ($anterior === 0) {
                        unset($this->carritoPromociones[$promocion->id]);
                    } else {
                        $this->carritoPromociones[$promocion->id] = $anterior;
                    }

                    $this->dispatch(
                        'toast',
                        message: 'Stock insuficiente. Ajusta la cantidad o activa la venta con stock negativo en Configuración.',
                        type: 'warning',
                    );
                }
            }
        } finally {
            $this->sincronizarCarritoConCatalogo();
        }
    }

    /**
     * Remove a promotion from the sale detail.
     */
    public function quitarPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDelCarrito($promocionId);

        unset($this->carritoPromociones[$promocion->id]);
        $this->sincronizarCarritoConCatalogo();
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
        $this->sincronizarCarritoConCatalogo();
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

        $lineas = $this->lineasDesde($this->carrito);
        $lineasPromociones = $this->lineasPromocionDesde($this->carritoPromociones);

        if ($lineas->isEmpty() && $lineasPromociones->isEmpty()) {
            $this->addError('carrito', 'Agrega al menos un ítem antes de pagar.');

            return;
        }

        if (! $this->negocioPermiteStockNegativo() && $this->hayAdvertenciasStock($lineas, $lineasPromociones)) {
            $this->addError('carrito', 'Stock insuficiente. Ajusta las cantidades antes de cobrar.');

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

        // Authoritative guard: the cart may be stale or built before a config change.
        if (! $negocio->permitirStockNegativo() && $this->hayAdvertenciasStock($lineas, $lineasPromociones)) {
            $this->addError('carrito', 'Stock insuficiente. Ajusta las cantidades o activa «Permitir vender con stock negativo» en Configuración.');

            return;
        }

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

            // Un solo UPDATE para los tres acumulados de la caja.
            $importe = number_format($totalEnCentavos / 100, 2, '.', '');
            $campoMetodo = $metodoPago === MetodoPago::Efectivo ? 'total_efectivo' : 'total_qr';

            Caja::query()
                ->whereKey($caja->id)
                ->update([
                    'cantidad_ventas' => DB::raw('cantidad_ventas + 1'),
                    'total_ventas' => DB::raw('total_ventas + '.$importe),
                    $campoMetodo => DB::raw($campoMetodo.' + '.$importe),
                    'updated_at' => now(),
                ]);

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

            // Un bloqueo y un UPDATE para todo el carrito, sin importar cuántos
            // productos (ni componentes de promoción) lleven.
            $idsProductos = $requerimientosStock
                ->keys()
                ->map(fn ($id): int => (int) $id)
                ->all();

            $bloqueados = $idsProductos === []
                ? collect()
                : Producto::query()
                    ->whereIn('id', $idsProductos)
                    ->lockForUpdate()
                    ->get(['id', 'nombre', 'stock'])
                    ->keyBy('id');

            $decrementos = collect();

            foreach ($requerimientosStock as $id => $requerimiento) {
                $producto = $bloqueados->get((int) $id);

                if ($producto === null || $producto->stock === null) {
                    continue;
                }

                $cantidad = max(0, (int) $requerimiento['cantidad']);

                if ($cantidad === 0) {
                    continue;
                }

                $nuevoStock = (int) $producto->stock - $cantidad;
                $decrementos->put((int) $id, $cantidad);

                if ($nuevoStock < 0) {
                    $advertenciasStock[] = $producto->nombre.' ('.$nuevoStock.')';

                    if (! $negocio->permitirStockNegativo()) {
                        throw ValidationException::withMessages([
                            'carrito' => 'Stock insuficiente para '.$producto->nombre.'. Ajusta la cantidad o activa la venta con stock negativo en Configuración.',
                        ]);
                    }
                }
            }

            if ($decrementos->isNotEmpty()) {
                $casos = $decrementos
                    ->map(fn (int $cantidad, int $id): string => "WHEN {$id} THEN stock - {$cantidad}")
                    ->implode(' ');

                DB::table('productos')
                    ->whereIn('id', $decrementos->keys()->all())
                    ->update([
                        'stock' => DB::raw("CASE id {$casos} ELSE stock END"),
                        'updated_at' => now(),
                    ]);
            }

            // El stock de la promoción se descuenta por combo vendido y puede quedar negativo.
            $cantidadesPromociones = $lineasPromociones
                ->filter(fn (array $linea): bool => $linea['promocion']->stock !== null && $linea['cantidad'] > 0)
                ->groupBy(fn (array $linea): int => (int) $linea['promocion']->id)
                ->map(fn (Collection $lineas): int => $lineas->sum('cantidad'));

            if ($cantidadesPromociones->isNotEmpty()) {
                $promocionesBloqueadas = Promocion::query()
                    ->whereIn('id', $cantidadesPromociones->keys()->all())
                    ->lockForUpdate()
                    ->get(['id', 'nombre', 'stock'])
                    ->keyBy('id');

                $decrementosPromociones = collect();

                foreach ($cantidadesPromociones as $id => $cantidad) {
                    $promocion = $promocionesBloqueadas->get((int) $id);

                    if ($promocion === null || $promocion->stock === null) {
                        continue;
                    }

                    $cantidad = max(0, (int) $cantidad);

                    if ($cantidad === 0) {
                        continue;
                    }

                    $nuevoStock = (int) $promocion->stock - $cantidad;
                    $decrementosPromociones->put((int) $id, $cantidad);

                    if ($nuevoStock <= 0) {
                        $advertenciasStock[] = 'Promoción '.$promocion->nombre.' ('.$nuevoStock.')';

                        if ($nuevoStock < 0 && ! $negocio->permitirStockNegativo()) {
                            throw ValidationException::withMessages([
                                'carrito' => 'Stock insuficiente para la promoción '.$promocion->nombre.'. Ajusta la cantidad o activa la venta con stock negativo en Configuración.',
                            ]);
                        }
                    }
                }

                if ($decrementosPromociones->isNotEmpty()) {
                    $casos = $decrementosPromociones
                        ->map(fn (int $cantidad, int $id): string => "WHEN {$id} THEN stock - {$cantidad}")
                        ->implode(' ');

                    DB::table('promociones')
                        ->whereIn('id', $decrementosPromociones->keys()->all())
                        ->update([
                            'stock' => DB::raw("CASE id {$casos} ELSE stock END"),
                            'updated_at' => now(),
                        ]);
                }
            }

            return $venta;
        });

        // Los decrementos de stock usan query builder y no disparan eventos de modelo.
        CatalogoCache::invalidar($negocio->id);

        // Snapshot the sold units: the cart is cleared below, but the catalog
        // keeps a floating badge with what was just sold on every product.
        session([
            'ultima_venta_unidades' => [
                'productos' => collect($this->carrito)
                    ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
                    ->map(fn ($cantidad): int => (int) $cantidad)
                    ->all(),
                'promociones' => collect($this->carritoPromociones)
                    ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
                    ->map(fn ($cantidad): int => (int) $cantidad)
                    ->all(),
            ],
        ]);

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
            $mensaje .= ' Atención: sin stock en '.implode(', ', $advertenciasStock).'.';
        }

        $this->sincronizarCarritoConCatalogo();
        $this->dispatch('stockActualizado');
        session()->flash('status', $mensaje);
    }

    /**
     * Refresh stock-derived warnings when another component changes the catalog.
     */
    #[On('stockActualizado')]
    public function actualizarPorStock(): void
    {
        // The render method resolves the cart from the database again.
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
        $permitirStockNegativo = $negocio?->permitirStockNegativo() ?? false;
        $hayExcesoStock = ! $permitirStockNegativo && $hayAdvertenciasStock;

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
            && ($this->metodoPago !== MetodoPago::Efectivo->value || $efectivoSuficiente)
            && ! $hayExcesoStock;

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
            'permitirStockNegativo' => $permitirStockNegativo,
            'hayExcesoStock' => $hayExcesoStock,
            'imprimirTicketPorDefecto' => $negocio?->imprimirTicketPorDefecto() ?? false,
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
            ->whereHas('productos', fn (Builder $query) => $query->where('productos.activa', true))
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
        return $negocio === null ? collect() : CatalogoCache::todo($negocio)[CatalogoCache::PROMOCIONES];
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
        $productosSinStock = $this->requerimientosStock($lineas, $lineasPromociones)
            ->contains(fn (array $requerimiento) => $requerimiento['producto']->stock !== null
                && $requerimiento['cantidad'] > $requerimiento['producto']->stock);

        if ($productosSinStock) {
            return true;
        }

        return $lineasPromociones->contains(fn (array $linea): bool => $linea['promocion']->stock !== null
            && ($linea['promocion']->stock <= 0 || $linea['cantidad'] > $linea['promocion']->stock));
    }

    /**
     * Scope a product lookup to an available product of the authenticated business.
     */
    private function productoDisponible(int $productoId): Producto
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 404);

        $producto = Producto::query()
            ->activas()
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
            ->activas()
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

    /**
     * Push the current cart quantities to the catalog child so its floating
     * badges update even though the change originated from a child event.
     */
    private function sincronizarCarritoConCatalogo(): void
    {
        $this->dispatch(
            'carritoActualizado',
            productos: collect($this->carrito)
                ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
                ->map(fn ($cantidad): int => (int) $cantidad)
                ->all(),
            promociones: collect($this->carritoPromociones)
                ->filter(fn ($cantidad) => is_numeric($cantidad) && (int) $cantidad > 0)
                ->map(fn ($cantidad): int => (int) $cantidad)
                ->all(),
        );
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

    private function negocioPermiteStockNegativo(): bool
    {
        return (bool) Auth::user()?->negocio?->permitirStockNegativo();
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
