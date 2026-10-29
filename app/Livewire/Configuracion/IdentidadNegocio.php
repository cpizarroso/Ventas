<?php

namespace App\Livewire\Configuracion;

use App\Livewire\Concerns\FlashesSavedStatus;
use App\Models\Negocio;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class IdentidadNegocio extends Component
{
    use FlashesSavedStatus, WithFileUploads;

    public string $nombre_comercial = '';

    public ?string $eslogan = null;

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
            'logo_nuevo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'qr_nuevo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'nombre_comercial' => 'nombre comercial',
            'eslogan' => 'eslogan',
            'logo_nuevo' => 'logo',
            'qr_nuevo' => 'QR de pago',
        ]);

        if ($this->logo_nuevo !== null) {
            if ($negocio->logo !== null) {
                Storage::disk('public')->delete($negocio->logo);
            }

            $negocio->logo = $this->logo_nuevo->store('logos', 'public');
        }

        if ($this->qr_nuevo !== null) {
            if ($negocio->qr_pago !== null) {
                Storage::disk('public')->delete($negocio->qr_pago);
            }

            $negocio->qr_pago = $this->qr_nuevo->store('qrs', 'public');
        }

        $negocio->nombre_comercial = trim($this->nombre_comercial);
        $negocio->eslogan = $this->eslogan !== null && trim($this->eslogan) !== ''
            ? trim($this->eslogan)
            : null;
        $negocio->save();

        $this->logo_actual = $negocio->logo;
        $this->logo_nuevo = null;
        $this->confirmarEliminacionLogo = false;
        $this->qr_actual = $negocio->qr_pago;
        $this->qr_nuevo = null;
        $this->confirmarEliminacionQr = false;

        $this->flashSaved();
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
    }

    public function render(): View
    {
        return view('livewire.configuracion.identidad-negocio');
    }

    private function negocioDelUsuario(): Negocio
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 403, 'Tu usuario no tiene un negocio asignado.');

        return $negocio;
    }
}
