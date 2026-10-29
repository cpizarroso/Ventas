<?php

namespace App\Livewire\Ventas;

use App\Enums\MetodoPago;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PantallaPrincipal extends Component
{
    private const STOCK_BAJO = 5;

    public ?int $categoriaSeleccionadaId = null;

    /**
     * Cantidades seleccionadas en la categoría abierta, indexadas por id de producto.
     *
     * @var array<int, int>
     */
    public array $cantidades = [];

    /**
     * Ítems aceptados para la venta, indexados por id de producto.
     *
     * @var array<int, int>
     */
    public array $carrito = [];

    public bool $mostrarDetalle = false;

    public ?int $ventaParaImprimirId = null;

    public ?string $metodoPago = null;

    public ?string $montoRecibido = null;

    public string $glosa = '';

    /**
     * Select a category on the sales screen.
     */
    public function seleccionar(int $categoriaId): void
    {
        $categoria = $this->categorias()
            ->whereKey($categoriaId)
            ->first();

        abort_if($categoria === null, 404);

        $this->categoriaSeleccionadaId = $categoria->id;
        $this->mostrarDetalle = false;
    }

    /**
     * Deselect the current category and go back to the grid.
     */
    public function deseleccionar(): void
    {
        $this->categoriaSeleccionadaId = null;
    }

    /**
     * Increase the quantity of a product in the selected category.
     */
    public function incrementar(int $productoId): void
    {
        $producto = $this->productoDelUsuario($productoId);

        $this->cantidades[$producto->id] = min(($this->cantidades[$producto->id] ?? 0) + 1, 999);
    }

    /**
     * Decrease the quantity of a product in the selected category.
     */
    public function disminuir(int $productoId): void
    {
        $producto = $this->productoDelUsuario($productoId);

        $cantidad = $this->cantidades[$producto->id] ?? 0;

        if ($cantidad <= 1) {
            unset($this->cantidades[$producto->id]);

            return;
        }

        $this->cantidades[$producto->id] = $cantidad - 1;
    }

    /**
     * Add the selected quantities of the open category to the sale detail.
     */
    public function aceptar(): void
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null || $this->categoriaSeleccionadaId === null, 404);

        $seleccion = $this->lineasDesde($this->cantidades, $this->categoriaSeleccionadaId);

        if ($seleccion->isEmpty()) {
            $this->addError('cantidades', 'Selecciona al menos un ítem antes de aceptar.');

            return;
        }

        foreach ($seleccion as $linea) {
            $productoId = $linea['producto']->id;
            $this->carrito[$productoId] = min(($this->carrito[$productoId] ?? 0) + $linea['cantidad'], 999);
        }

        $this->cantidades = [];
        $this->resetErrorBag('cantidades');
        $this->mostrarDetalle = true;
    }

    public function verDetalle(): void
    {
        $this->mostrarDetalle = true;
        $this->categoriaSeleccionadaId = null;
    }

    public function volverACategorias(): void
    {
        $this->mostrarDetalle = false;
        $this->categoriaSeleccionadaId = null;
    }

    /**
     * Increase the quantity of a product already accepted in the sale detail.
     */
    public function incrementarCarrito(int $productoId): void
    {
        $producto = $this->productoDelCarrito($productoId);

        $this->carrito[$producto->id] = min(($this->carrito[$producto->id] ?? 0) + 1, 999);
    }

    /**
     * Decrease the quantity of a product already accepted in the sale detail.
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

    public function vaciarCarrito(): void
    {
        $this->carrito = [];
        $this->metodoPago = null;
        $this->montoRecibido = null;
        $this->glosa = '';
        $this->resetErrorBag('carrito');
        $this->resetValidation(['metodoPago', 'montoRecibido', 'glosa']);
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
     * Persist the accepted items as a single sale and clear the cart.
     */
    public function cobrar(): void
    {
        $user = Auth::user();
        $negocio = $user?->negocio;

        abort_if($negocio === null, 404);

        $lineas = $this->lineasDesde($this->carrito);

        if ($lineas->isEmpty()) {
            $this->addError('carrito', 'Agrega al menos un ítem antes de cobrar.');

            return;
        }

        $totalEnCentavos = $lineas->sum('subtotal_centavos');

        $this->montoRecibido = $this->montoRecibido === null
            ? null
            : str_replace(',', '.', trim($this->montoRecibido));

        $this->glosa = trim($this->glosa);

        $datos = $this->validate([
            'metodoPago' => ['required', Rule::enum(MetodoPago::class)],
            'glosa' => ['nullable', 'string', 'max:255'],
        ]);

        $metodoPago = MetodoPago::from($datos['metodoPago']);
        $glosa = $this->glosa === '' ? null : $this->glosa;
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

        $venta = DB::transaction(function () use ($negocio, $user, $lineas, $totalEnCentavos, $metodoPago, $montoRecibidoEnCentavos, $cambioEnCentavos, $glosa, &$advertenciasStock) {
            $venta = $negocio->ventas()->create([
                'user_id' => $user->id,
                'total' => $totalEnCentavos / 100,
                'metodo_pago' => $metodoPago,
                'monto_recibido' => $montoRecibidoEnCentavos / 100,
                'cambio' => $cambioEnCentavos / 100,
                'glosa' => $glosa,
            ]);

            $venta->detalles()->createMany(
                $lineas->map(fn (array $linea) => [
                    'producto_id' => $linea['producto']->id,
                    'nombre' => $linea['producto']->nombre,
                    'precio_unitario' => $linea['precio_unitario'],
                    'cantidad' => $linea['cantidad'],
                    'subtotal' => $linea['subtotal'],
                ])->all()
            );

            foreach ($lineas as $linea) {
                $producto = Producto::query()
                    ->whereKey($linea['producto']->id)
                    ->lockForUpdate()
                    ->first();

                if ($producto === null || $producto->stock === null) {
                    continue;
                }

                $nuevoStock = $producto->stock - $linea['cantidad'];

                $producto->decrement('stock', $linea['cantidad']);

                if ($nuevoStock < 0) {
                    $advertenciasStock[] = $producto->nombre.' ('.$nuevoStock.')';
                }
            }

            return $venta;
        });

        $this->carrito = [];
        $this->cantidades = [];
        $this->mostrarDetalle = false;
        $this->categoriaSeleccionadaId = null;
        $this->metodoPago = null;
        $this->montoRecibido = null;
        $this->glosa = '';
        $this->ventaParaImprimirId = $venta->id;
        $this->resetErrorBag('carrito');
        $this->resetValidation(['metodoPago', 'montoRecibido', 'glosa']);

        $mensaje = 'Venta #'.$venta->id.' registrada por Bs '.number_format($totalEnCentavos / 100, 2)
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
    }

    public function render(): View
    {
        $user = Auth::user();
        $negocio = $user?->negocio;

        $categorias = $this->categorias()->get();
        $categoriaSeleccionada = $this->categoriaSeleccionadaId !== null
            ? $categorias->firstWhere('id', $this->categoriaSeleccionadaId)
            : null;

        $productos = $categoriaSeleccionada
            ? $categoriaSeleccionada->productos()->orderBy('nombre')->get()
            : collect();

        $cantidadesPorProducto = $productos->mapWithKeys(
            fn (Producto $producto) => [$producto->id => $this->cantidadClampeada($producto->id)]
        );

        $totalEnCentavos = $productos->sum(
            fn (Producto $producto) => $this->precioEnCentavos($producto) * $cantidadesPorProducto[$producto->id]
        );

        $lineasCarrito = $this->lineasDesde($this->carrito);
        $totalCarritoEnCentavos = $lineasCarrito->sum('subtotal_centavos');
        $hayAdvertenciasStock = $lineasCarrito->contains(
            fn (array $linea) => $linea['producto']->stock !== null
                && $linea['cantidad'] > $linea['producto']->stock
        );

        $montoRecibidoEnCentavos = $this->montoRecibido !== null && $this->montoRecibido !== ''
            ? $this->centavosDesde($this->montoRecibido)
            : null;

        $cambioEnCentavos = $montoRecibidoEnCentavos !== null
            ? max(0, $montoRecibidoEnCentavos - $totalCarritoEnCentavos)
            : 0;

        $efectivoSuficiente = $montoRecibidoEnCentavos !== null
            && $montoRecibidoEnCentavos >= $totalCarritoEnCentavos;

        $puedeCobrar = $totalCarritoEnCentavos > 0
            && $this->metodoPago !== null
            && ($this->metodoPago !== MetodoPago::Efectivo->value || $efectivoSuficiente);

        return view('livewire.ventas.pantalla-principal', [
            'negocio' => $negocio,
            'categorias' => $categorias,
            'categoriaSeleccionada' => $categoriaSeleccionada,
            'productos' => $productos,
            'cantidadesPorProducto' => $cantidadesPorProducto,
            'total' => number_format($totalEnCentavos / 100, 2),
            'haySeleccion' => $totalEnCentavos > 0,
            'lineasCarrito' => $lineasCarrito,
            'totalCarrito' => number_format($totalCarritoEnCentavos / 100, 2),
            'cantidadItems' => $lineasCarrito->sum('cantidad'),
            'puedeConfigurar' => $user?->puedeGestionarConfiguracion() ?? false,
            'cambio' => number_format($cambioEnCentavos / 100, 2),
            'efectivoSuficiente' => $efectivoSuficiente,
            'puedeCobrar' => $puedeCobrar,
            'hayAdvertenciasStock' => $hayAdvertenciasStock,
            'umbralStockBajo' => self::STOCK_BAJO,
        ]);
    }

    /**
     * Active categories of the authenticated user's business, ordered for display.
     *
     * @return Builder<Categoria>
     */
    private function categorias()
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return Categoria::query()->whereRaw('1 = 0');
        }

        return $negocio->categorias()
            ->activas()
            ->orderBy('orden')
            ->orderBy('nombre');
    }

    /**
     * Scope a product lookup to the selected category of the authenticated business.
     */
    private function productoDelUsuario(int $productoId): Producto
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null || $this->categoriaSeleccionadaId === null, 404);

        $producto = Producto::query()
            ->whereKey($productoId)
            ->where('categoria_id', $this->categoriaSeleccionadaId)
            ->whereHas('categoria', fn (Builder $query) => $query->where('negocio_id', $negocio->id))
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
    private function lineasDesde(array $cantidades, ?int $categoriaId = null): Collection
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return collect();
        }

        $normalizadas = collect($cantidades)
            ->map(fn ($cantidad) => is_numeric($cantidad) ? (int) $cantidad : 0)
            ->filter(fn (int $cantidad) => $cantidad > 0)
            ->map(fn (int $cantidad) => min($cantidad, 999));

        if ($normalizadas->isEmpty()) {
            return collect();
        }

        $query = Producto::query()
            ->with('categoria')
            ->whereIn('id', $normalizadas->keys())
            ->whereHas('categoria', fn (Builder $subquery) => $subquery->where('negocio_id', $negocio->id));

        if ($categoriaId !== null) {
            $query->where('categoria_id', $categoriaId);
        }

        return $query->orderBy('nombre')->get()->map(function (Producto $producto) use ($normalizadas) {
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

    private function cantidadClampeada(int $productoId): int
    {
        $cantidad = $this->cantidades[$productoId] ?? 0;

        return is_numeric($cantidad) ? max(0, min((int) $cantidad, 999)) : 0;
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
