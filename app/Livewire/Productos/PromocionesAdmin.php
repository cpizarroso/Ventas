<?php

namespace App\Livewire\Productos;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PromocionesAdmin extends Component
{
    use FlashesSavedStatus, OptimizaImagenes, WithFileUploads, WithPagination;

    public string $nombre = '';

    public string $descripcion = '';

    public string $precio = '';

    public string $promocionStock = '';

    public bool $promocionStockIlimitado = true;

    public string $fechaInicio = '';

    public string $fechaFin = '';

    /** @var array<int, int|string> */
    public array $diasSemana = [1, 2, 3, 4, 5, 6, 7];

    /** @var array<int, array{producto_id: int|string, cantidad: int|string}> */
    public array $items = [['producto_id' => '', 'cantidad' => 1]];

    /** @var TemporaryUploadedFile|null */
    public $promocionImagen = null;

    public bool $quitarImagen = false;

    public bool $activa = true;

    public ?int $editandoId = null;

    public bool $modalAbierto = false;

    public ?string $imagenAmpliadaUrl = null;

    public string $imagenAmpliadaNombre = '';

    #[Url(as: 'buscar-promociones')]
    public string $buscar = '';

    public function mount(): void
    {
        $this->resetForm();
    }

    public function placeholder(): View
    {
        return view('components.configuracion-modulo-placeholder', ['titulo' => 'promociones']);
    }

    /**
     * Keep this paginator out of the other product page query strings.
     *
     * @return array<string, array<string, mixed>>
     */
    public function queryString(): array
    {
        return [
            'paginators.page' => ['as' => 'pagina-promociones-productos', 'history' => false],
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function abrirModal(): void
    {
        $this->authorize('create', Promocion::class);

        $this->resetForm();
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->resetForm();
    }

    /**
     * Add an empty item row to the promotion form.
     */
    public function agregarItem(): void
    {
        $this->items[] = ['producto_id' => '', 'cantidad' => 1];
    }

    /**
     * Remove an item row from the promotion form.
     */
    public function quitarItem(int $indice): void
    {
        unset($this->items[$indice]);

        $this->items = array_values($this->items);

        if ($this->items === []) {
            $this->items = [['producto_id' => '', 'cantidad' => 1]];
        }
    }

    /**
     * Load a promotion into the form for editing.
     */
    public function editar(int $promocionId): void
    {
        $promocion = $this->promocionDelUsuario($promocionId);

        $this->authorize('update', $promocion);

        $this->editandoId = $promocion->id;
        $this->nombre = $promocion->nombre;
        $this->descripcion = $promocion->descripcion ?? '';
        $this->precio = (string) $promocion->precio;
        $this->promocionStock = $promocion->stock === null ? '' : (string) $promocion->stock;
        $this->promocionStockIlimitado = $promocion->stock === null;
        $this->fechaInicio = $promocion->fecha_inicio->format('Y-m-d');
        $this->fechaFin = $promocion->fecha_fin->format('Y-m-d');
        $this->diasSemana = collect($promocion->dias_semana ?? [])
            ->map(fn (int|string $dia): int => (int) $dia)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $this->items = $promocion->productos
            ->map(fn (Producto $producto): array => [
                'producto_id' => $producto->id,
                'cantidad' => (int) $producto->pivot->cantidad,
            ])
            ->values()
            ->all();

        if ($this->items === []) {
            $this->items = [['producto_id' => '', 'cantidad' => 1]];
        }

        $this->promocionImagen = null;
        $this->quitarImagen = false;
        $this->activa = $promocion->activa;
        $this->modalAbierto = true;
        $this->resetValidation();
    }

    public function cancelarEdicion(): void
    {
        $this->resetForm();
    }

    /**
     * Create or update the promotion currently in the form.
     */
    public function guardar(): void
    {
        $promocion = $this->editandoId !== null
            ? $this->promocionDelUsuario($this->editandoId)
            : null;

        if ($promocion !== null) {
            $this->authorize('update', $promocion);
        } else {
            $this->authorize('create', Promocion::class);
        }

        $this->nombre = trim($this->nombre);
        $this->descripcion = trim($this->descripcion);
        $this->precio = str_replace(',', '.', trim($this->precio));

        if ($this->promocionStockIlimitado) {
            $this->promocionStock = '';
        }

        $this->validate([
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:80',
                Rule::unique('promociones', 'nombre')
                    ->where('negocio_id', Auth::user()->negocio_id)
                    ->ignore($promocion?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0.01', 'max:99999999.99', 'decimal:0,2'],
            'promocionStock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'fechaInicio' => ['required', 'date_format:Y-m-d'],
            'fechaFin' => ['required', 'date_format:Y-m-d', 'after_or_equal:fechaInicio'],
            'diasSemana' => ['required', 'array', 'min:1'],
            'diasSemana.*' => ['integer', 'between:1,7', 'distinct'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => [
                'required',
                'integer',
                'distinct',
                Rule::in($this->productosDelNegocio()->pluck('id')->all()),
            ],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],
            'promocionImagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'activa' => ['boolean'],
        ], [
            'nombre.unique' => 'Ya existe una promoción con ese nombre en este negocio.',
            'precio.decimal' => 'El precio admite como máximo dos decimales.',
            'promocionStock.integer' => 'El stock debe ser un número entero.',
            'promocionStock.min' => 'El stock no puede ser negativo.',
            'fechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'diasSemana.required' => 'Selecciona al menos un día para la promoción.',
            'items.required' => 'Agrega al menos un ítem a la promoción.',
            'items.*.producto_id.distinct' => 'No puedes repetir un ítem en la misma promoción.',
            'items.*.producto_id.in' => 'Uno de los ítems seleccionados no pertenece a este negocio.',
            'items.*.cantidad.integer' => 'La cantidad de cada ítem debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad de cada ítem debe ser al menos 1.',
            'promocionImagen.image' => 'La imagen debe ser un archivo de imagen válido.',
            'promocionImagen.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'promocionImagen.max' => 'La imagen no puede superar los 2 MB.',
        ], [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'precio' => 'precio',
            'promocionStock' => 'stock',
            'fechaInicio' => 'fecha de inicio',
            'fechaFin' => 'fecha de fin',
            'diasSemana' => 'días',
            'items' => 'ítems',
            'promocionImagen' => 'imagen',
        ]);

        $productos = $this->productosDelNegocio()->keyBy('id');
        $totalNormalEnCentavos = collect($this->items)->sum(function (array $item) use ($productos): int {
            $producto = $productos->get((int) $item['producto_id']);

            if ($producto === null) {
                return 0;
            }

            return (int) round((float) $producto->precio * 100) * (int) $item['cantidad'];
        });
        $precioEnCentavos = (int) round((float) $this->precio * 100);

        if ($precioEnCentavos > $totalNormalEnCentavos) {
            $this->addError('precio', 'El precio promocional no puede superar el total normal de los ítems seleccionados.');

            return;
        }

        $imagen = $promocion?->imagen;

        if ($this->promocionImagen !== null) {
            $this->eliminarImagen($imagen);
            $imagen = $this->guardarImagenOptimizada($this->promocionImagen, 'promociones', 'producto');
        } elseif ($this->quitarImagen && $promocion !== null) {
            $this->eliminarImagen($imagen);
            $imagen = null;
        }

        $datos = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion === '' ? null : $this->descripcion,
            'imagen' => $imagen,
            'precio' => $this->precio,
            'stock' => $this->promocionStockIlimitado || $this->promocionStock === ''
                ? null
                : (int) $this->promocionStock,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
            'dias_semana' => collect($this->diasSemana)
                ->map(fn (int|string $dia): int => (int) $dia)
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'activa' => $this->activa,
        ];
        $items = collect($this->items)
            ->mapWithKeys(fn (array $item): array => [
                (int) $item['producto_id'] => ['cantidad' => (int) $item['cantidad']],
            ])
            ->all();

        DB::transaction(function () use ($promocion, $datos, $items): void {
            if ($promocion !== null) {
                $promocion->update($datos);
            } else {
                $promocion = Auth::user()->negocio->promociones()->create($datos);
            }

            $promocion->productos()->sync($items);
        });

        $this->resetForm();
        $this->flashSaved();
    }

    /**
     * Deactivate (or reactivate) a promotion without deleting it or its history.
     */
    public function cambiarActiva(int $promocionId): void
    {
        if ($this->modalAbierto) {
            return;
        }

        $promocion = $this->promocionDelUsuario($promocionId);

        $this->authorize('cambiarActiva', $promocion);

        $activa = ! $promocion->activa;
        $promocion->update(['activa' => $activa]);

        $mensaje = $activa
            ? 'La promoción se activó y ya aparecerá en ventas.'
            : 'La promoción se dio de baja y no aparecerá en ventas.';

        session()->flash('status', $mensaje);
        $this->dispatch('toast', message: $mensaje);
    }

    /**
     * Human-readable status for the promotion list.
     *
     * @return array{texto: string, clases: string}
     */
    public function estado(Promocion $promocion): array
    {
        if (! $promocion->activa) {
            return [
                'texto' => 'Inactiva',
                'clases' => 'bg-stone-100 text-stone-600 dark:bg-stone-700 dark:text-stone-300',
            ];
        }

        $hoy = now()->startOfDay();

        if ($promocion->fecha_inicio->gt($hoy)) {
            return [
                'texto' => 'Programada',
                'clases' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
            ];
        }

        if ($promocion->fecha_fin->lt($hoy)) {
            return [
                'texto' => 'Vencida',
                'clases' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
            ];
        }

        if (! in_array($hoy->isoWeekday(), array_map('intval', $promocion->dias_semana ?? []), true)) {
            return [
                'texto' => 'Hoy no aplica',
                'clases' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            ];
        }

        return [
            'texto' => 'Vigente',
            'clases' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        ];
    }

    public function abrirImagenPromocion(int $promocionId): void
    {
        $promocion = $this->promocionDelUsuario($promocionId);
        $url = $promocion->imagenUrl();

        if ($url === null) {
            return;
        }

        $this->imagenAmpliadaUrl = $url;
        $this->imagenAmpliadaNombre = $promocion->nombre;
    }

    public function cerrarImagenAmpliada(): void
    {
        $this->reset('imagenAmpliadaUrl', 'imagenAmpliadaNombre');
    }

    /**
     * Switch between limited and unlimited promotion stock.
     */
    public function alternarStockIlimitado(): void
    {
        if (! $this->modalAbierto) {
            return;
        }

        $this->promocionStockIlimitado = ! $this->promocionStockIlimitado;

        if ($this->promocionStockIlimitado) {
            $this->promocionStock = '';
        }
    }

    public function updatedPromocionImagen(): void
    {
        if ($this->promocionImagen !== null) {
            $this->quitarImagen = false;
        }
    }

    /**
     * Mark the current image for removal when editing.
     */
    public function alternarQuitarImagen(): void
    {
        if ($this->editandoId === null) {
            return;
        }

        $this->quitarImagen = ! $this->quitarImagen;

        if ($this->quitarImagen) {
            $this->promocionImagen = null;
        }
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;
        $buscar = trim($this->buscar);

        $promociones = ($negocio !== null ? $negocio->promociones() : Promocion::query()->whereRaw('1 = 0'))
            ->with('productos.categoria')
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $patron = '%'.$buscar.'%';

                $query->where(function ($query) use ($patron): void {
                    $query->where('nombre', 'like', $patron)
                        ->orWhere('descripcion', 'like', $patron)
                        ->orWhereHas('productos', fn ($productos) => $productos->where('nombre', 'like', $patron));
                });
            })
            ->orderByDesc('activa')
            ->orderByDesc('fecha_inicio')
            ->orderBy('nombre')
            ->paginate(10);

        return view('livewire.productos.promociones-admin', [
            'promociones' => $promociones,
            'productos' => $this->productosDelNegocio(),
            'promocionEditando' => $this->promocionEditando(),
        ]);
    }

    /**
     * Products of the authenticated user's business, ordered for the selector.
     *
     * @return Collection<int, Producto>
     */
    private function productosDelNegocio(): Collection
    {
        $negocio = Auth::user()?->negocio;

        if ($negocio === null) {
            return collect();
        }

        return Producto::query()
            ->activas()
            ->with('categoria')
            ->whereHas('categoria', fn ($query) => $query->where('negocio_id', $negocio->id))
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Scope a promotion lookup to the authenticated user's business.
     */
    private function promocionDelUsuario(int $promocionId): Promocion
    {
        $user = Auth::user();

        abort_if($user->negocio_id === null, 403, 'Tu usuario no tiene un negocio asignado.');

        $promocion = $user->negocio->promociones()
            ->with('productos')
            ->whereKey($promocionId)
            ->first();

        abort_if($promocion === null, 404);

        return $promocion;
    }

    private function promocionEditando(): ?Promocion
    {
        return $this->editandoId === null ? null : $this->promocionDelUsuario($this->editandoId);
    }

    private function resetForm(): void
    {
        $this->reset(
            'nombre',
            'descripcion',
            'precio',
            'promocionStock',
            'promocionStockIlimitado',
            'fechaInicio',
            'fechaFin',
            'diasSemana',
            'items',
            'promocionImagen',
            'quitarImagen',
            'activa',
            'editandoId',
            'modalAbierto',
            'imagenAmpliadaUrl',
            'imagenAmpliadaNombre',
        );

        $this->fechaInicio = now()->toDateString();
        $this->fechaFin = now()->addWeek()->toDateString();
        $this->diasSemana = [1, 2, 3, 4, 5, 6, 7];
        $this->items = [['producto_id' => '', 'cantidad' => 1]];
        $this->promocionStockIlimitado = true;
        $this->activa = true;

        $this->resetValidation();
    }
}
