<?php

namespace App\Livewire\Configuracion;

use App\Enums\MetodoPago;
use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Models\Negocio;
use App\Support\CatalogoCache;
use App\Support\LogoDerivado;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class IdentidadNegocio extends Component
{
    use FlashesSavedStatus, OptimizaImagenes, WithFileUploads;

    public string $nombre_comercial = '';

    public ?string $eslogan = null;

    public string $metodo_pago_predeterminado = MetodoPago::Efectivo->value;

    public bool $imprimir_ticket_predeterminado = false;

    public bool $mostrar_modal_impresion = true;

    public int $stock_min = 10;

    public bool $permitir_stock_negativo = false;

    /** @var TemporaryUploadedFile|null */
    public $logo_nuevo = null;

    public ?string $logo_actual = null;

    public bool $confirmarEliminacionLogo = false;

    /** @var TemporaryUploadedFile|null */
    public $qr_nuevo = null;

    public ?string $qr_actual = null;

    public bool $confirmarEliminacionQr = false;

    /** 'logo' o 'qr'; controla el modal de imagen ampliada. */
    public ?string $imagenAmpliada = null;

    public function mount(): void
    {
        $negocio = $this->negocioDelUsuario();

        $this->nombre_comercial = $negocio->nombre_comercial;
        $this->eslogan = $negocio->eslogan;
        $this->metodo_pago_predeterminado = $negocio->metodoPagoPredeterminado()->value;
        $this->imprimir_ticket_predeterminado = $negocio->imprimirTicketPorDefecto();
        $this->mostrar_modal_impresion = $negocio->mostrarModalImpresion();
        $this->stock_min = $negocio->stockMinimo();
        $this->permitir_stock_negativo = $negocio->permitirStockNegativo();
        $this->logo_actual = $negocio->logo;
        $this->qr_actual = $negocio->qr_pago;
    }

    /**
     * Update the visual identity of the authenticated user's business.
     */
    public function guardar(): void
    {
        $negocio = $this->negocioDelUsuario();

        $this->authorize('update', $negocio);

        $this->validate([
            'nombre_comercial' => ['required', 'string', 'min:2', 'max:120'],
            'eslogan' => ['nullable', 'string', 'max:160'],
            'metodo_pago_predeterminado' => ['required', Rule::enum(MetodoPago::class)],
            'imprimir_ticket_predeterminado' => ['boolean'],
            'mostrar_modal_impresion' => ['boolean'],
            'stock_min' => ['required', 'integer', 'min:0', 'max:999999'],
            'permitir_stock_negativo' => ['boolean'],
            'logo_nuevo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'qr_nuevo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'nombre_comercial' => 'nombre comercial',
            'eslogan' => 'eslogan',
            'metodo_pago_predeterminado' => 'método de pago por defecto',
            'imprimir_ticket_predeterminado' => 'imprimir ticket por defecto',
            'mostrar_modal_impresion' => 'mostrar modal de impresión',
            'stock_min' => 'stock mínimo',
            'permitir_stock_negativo' => 'permitir venta con stock negativo',
            'logo_nuevo' => 'logo',
            'qr_nuevo' => 'QR de pago',
        ]);

        $logoAnterior = $negocio->logo;

        if ($this->logo_nuevo !== null) {
            if ($logoAnterior !== null) {
                Storage::disk('public')->delete($logoAnterior);
                LogoDerivado::eliminar($logoAnterior);
            }

            $negocio->logo = $this->guardarImagenOptimizada($this->logo_nuevo, 'logos', 'logo');
        }

        if ($this->qr_nuevo !== null) {
            if ($negocio->qr_pago !== null) {
                Storage::disk('public')->delete($negocio->qr_pago);
            }

            $negocio->qr_pago = $this->guardarImagenOptimizada($this->qr_nuevo, 'qrs', 'qr');
        }

        $negocio->nombre_comercial = trim($this->nombre_comercial);
        $negocio->eslogan = $this->eslogan !== null && trim($this->eslogan) !== ''
            ? trim($this->eslogan)
            : null;
        $negocio->metodo_pago_predeterminado = $this->metodo_pago_predeterminado;
        $negocio->imprimir_ticket_predeterminado = $this->imprimir_ticket_predeterminado;
        $negocio->mostrar_modal_impresion = $this->mostrar_modal_impresion;
        $negocio->stock_min = $this->stock_min;
        $negocio->permitir_stock_negativo = $this->permitir_stock_negativo;
        $negocio->save();

        // Cached products carry the business relation, so the effective minimum
        // would otherwise stay stale until the cache expires.
        CatalogoCache::invalidar($negocio->id);

        LogoDerivado::sincronizar($negocio);

        $this->logo_actual = $negocio->logo;
        $this->logo_nuevo = null;
        $this->confirmarEliminacionLogo = false;
        $this->qr_actual = $negocio->qr_pago;
        $this->qr_nuevo = null;
        $this->confirmarEliminacionQr = false;
        $this->imagenAmpliada = null;

        $this->dispatch('stockActualizado');
        $this->flashSaved();

        $this->js('window.location.reload()');
    }

    /**
     * Persist the logo as soon as it is uploaded.
     */
    public function updatedLogoNuevo(): void
    {
        $this->guardarImagenCargada('logo_nuevo', 'logo', 'logos', 'logo', 'El logo se guardó correctamente.');
    }

    /**
     * Persist the payment QR as soon as it is uploaded.
     */
    public function updatedQrNuevo(): void
    {
        $this->guardarImagenCargada('qr_nuevo', 'qr_pago', 'qrs', 'qr', 'El QR de pago se guardó correctamente.');
    }

    /**
     * Remove the current logo and fall back to the default icon.
     */
    public function eliminarLogo(): void
    {
        $negocio = $this->negocioDelUsuario();

        $this->authorize('update', $negocio);

        if ($negocio->logo !== null) {
            $logoAnterior = $negocio->logo;
            Storage::disk('public')->delete($logoAnterior);
            LogoDerivado::eliminar($logoAnterior);
            $negocio->logo = null;
            $negocio->save();
        }

        $this->logo_actual = null;
        $this->logo_nuevo = null;
        $this->confirmarEliminacionLogo = false;
        $this->imagenAmpliada = null;

        session()->flash('status', 'El logo se eliminó correctamente.');

        $this->js('window.location.reload()');
    }

    /**
     * Remove the current payment QR image.
     */
    public function eliminarQr(): void
    {
        $negocio = $this->negocioDelUsuario();

        $this->authorize('update', $negocio);

        if ($negocio->qr_pago !== null) {
            Storage::disk('public')->delete($negocio->qr_pago);
            $negocio->qr_pago = null;
            $negocio->save();
        }

        $this->qr_actual = null;
        $this->qr_nuevo = null;
        $this->confirmarEliminacionQr = false;
        $this->imagenAmpliada = null;

        session()->flash('status', 'El QR de pago se eliminó correctamente.');

        $this->js('window.location.reload()');
    }

    public function logoActualUrl(): ?string
    {
        return $this->versionedStorageUrl($this->logo_actual);
    }

    public function qrActualUrl(): ?string
    {
        return $this->versionedStorageUrl($this->qr_actual);
    }

    public function abrirImagenLogo(): void
    {
        if (! $this->logo_nuevo?->isPreviewable() && $this->logoActualUrl() === null) {
            return;
        }

        $this->imagenAmpliada = 'logo';
    }

    public function abrirImagenQr(): void
    {
        if (! $this->qr_nuevo?->isPreviewable() && $this->qrActualUrl() === null) {
            return;
        }

        $this->imagenAmpliada = 'qr';
    }

    public function cerrarImagenAmpliada(): void
    {
        $this->reset('imagenAmpliada');
    }

    public function urlImagenAmpliada(): ?string
    {
        if ($this->imagenAmpliada === 'logo') {
            return $this->logo_nuevo?->isPreviewable()
                ? $this->logo_nuevo->temporaryUrl()
                : $this->logoActualUrl();
        }

        if ($this->imagenAmpliada === 'qr') {
            return $this->qr_nuevo?->isPreviewable()
                ? $this->qr_nuevo->temporaryUrl()
                : $this->qrActualUrl();
        }

        return null;
    }

    public function tituloImagenAmpliada(): string
    {
        return match ($this->imagenAmpliada) {
            'logo' => 'Logo de '.$this->nombre_comercial,
            'qr' => 'QR de pago de '.$this->nombre_comercial,
            default => '',
        };
    }

    public function render(): View
    {
        return view('livewire.configuracion.identidad-negocio', [
            'nombre_comercial' => $this->nombre_comercial,
            'eslogan' => $this->eslogan,
            'metodo_pago_predeterminado' => $this->metodo_pago_predeterminado,
            'imprimir_ticket_predeterminado' => $this->imprimir_ticket_predeterminado,
            'mostrar_modal_impresion' => $this->mostrar_modal_impresion,
            'stock_min' => $this->stock_min,
            'permitir_stock_negativo' => $this->permitir_stock_negativo,
            'logo_nuevo' => $this->logo_nuevo,
            'logo_actual' => $this->logo_actual,
            'confirmarEliminacionLogo' => $this->confirmarEliminacionLogo,
            'qr_nuevo' => $this->qr_nuevo,
            'qr_actual' => $this->qr_actual,
            'confirmarEliminacionQr' => $this->confirmarEliminacionQr,
            'imagenAmpliada' => $this->imagenAmpliada,
            'urlLogoActual' => $this->logoActualUrl(),
            'urlQrActual' => $this->qrActualUrl(),
            'urlImagenAmpliada' => $this->urlImagenAmpliada(),
            'tituloImagenAmpliada' => $this->tituloImagenAmpliada(),
        ]);
    }

    private function versionedStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $disk = Storage::disk('public');
        $url = $disk->url($path);

        if (! $disk->exists($path)) {
            return $url;
        }

        return $url.'?v='.$disk->lastModified($path);
    }

    /**
     * Store an uploaded image immediately and notify the user.
     *
     * @param  'logo'|'qr'  $perfil
     */
    private function guardarImagenCargada(
        string $propiedad,
        string $columna,
        string $carpeta,
        string $perfil,
        string $mensaje,
    ): void {
        $negocio = $this->negocioDelUsuario();

        $this->authorize('update', $negocio);

        $this->validateOnly($propiedad, [
            $propiedad => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'logo_nuevo' => 'logo',
            'qr_nuevo' => 'QR de pago',
        ]);

        $archivo = $this->{$propiedad};

        if ($archivo === null) {
            return;
        }

        $rutaAnterior = $negocio->{$columna};
        $negocio->{$columna} = $this->guardarImagenOptimizada($archivo, $carpeta, $perfil);
        $negocio->save();

        if ($rutaAnterior !== null && $rutaAnterior !== $negocio->{$columna}) {
            Storage::disk('public')->delete($rutaAnterior);
        }

        if ($columna === 'logo') {
            LogoDerivado::sincronizar($negocio);

            if ($rutaAnterior !== null && $rutaAnterior !== $negocio->{$columna}) {
                LogoDerivado::eliminar($rutaAnterior);
            }

            $this->logo_actual = $negocio->logo;
            $this->logo_nuevo = null;
            $this->confirmarEliminacionLogo = false;
        } else {
            $this->qr_actual = $negocio->qr_pago;
            $this->qr_nuevo = null;
            $this->confirmarEliminacionQr = false;
        }

        $this->imagenAmpliada = null;

        session()->flash('status', $mensaje);

        $this->dispatch('toast', message: $mensaje);
    }

    private function negocioDelUsuario(): Negocio
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 403, 'Tu usuario no tiene un negocio asignado.');

        return $negocio;
    }
}
