<?php

namespace App\Livewire\Configuracion;

use App\Enums\MetodoPago;
use App\Livewire\Concerns\FlashesSavedStatus;
use App\Livewire\Concerns\OptimizaImagenes;
use App\Models\Negocio;
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

    /** @var TemporaryUploadedFile|null */
    public $logo_nuevo = null;

    public ?string $logo_actual = null;

    public bool $confirmarEliminacionLogo = false;

    /** @var TemporaryUploadedFile|null */
    public $qr_nuevo = null;

    public ?string $qr_actual = null;

    public bool $confirmarEliminacionQr = false;

    public function mount(): void
    {
        $negocio = $this->negocioDelUsuario();

        $this->nombre_comercial = $negocio->nombre_comercial;
        $this->eslogan = $negocio->eslogan;
        $this->metodo_pago_predeterminado = $negocio->metodoPagoPredeterminado()->value;
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
            'logo_nuevo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'qr_nuevo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], [
            'nombre_comercial' => 'nombre comercial',
            'eslogan' => 'eslogan',
            'metodo_pago_predeterminado' => 'método de pago por defecto',
            'logo_nuevo' => 'logo',
            'qr_nuevo' => 'QR de pago',
        ]);

        if ($this->logo_nuevo !== null) {
            if ($negocio->logo !== null) {
                Storage::disk('public')->delete($negocio->logo);
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
        $negocio->save();

        $this->logo_actual = $negocio->logo;
        $this->logo_nuevo = null;
        $this->confirmarEliminacionLogo = false;
        $this->qr_actual = $negocio->qr_pago;
        $this->qr_nuevo = null;
        $this->confirmarEliminacionQr = false;

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
            Storage::disk('public')->delete($negocio->logo);
            $negocio->logo = null;
            $negocio->save();
        }

        $this->logo_actual = null;
        $this->logo_nuevo = null;
        $this->confirmarEliminacionLogo = false;

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

    public function render(): View
    {
        return view('livewire.configuracion.identidad-negocio');
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
            $this->logo_actual = $negocio->logo;
            $this->logo_nuevo = null;
            $this->confirmarEliminacionLogo = false;
        } else {
            $this->qr_actual = $negocio->qr_pago;
            $this->qr_nuevo = null;
            $this->confirmarEliminacionQr = false;
        }

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
