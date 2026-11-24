<?php

namespace App\Models;

use App\Enums\MetodoPago;
use App\Services\FaviconGenerator;
use Database\Factories\NegocioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nombre_comercial', 'eslogan', 'logo', 'qr_pago', 'metodo_pago_predeterminado'])]
class Negocio extends Model
{
    /** @use HasFactory<NegocioFactory> */
    use HasFactory;

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class);
    }

    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class);
    }

    /**
     * Payment method preselected when opening the checkout modal.
     */
    public function metodoPagoPredeterminado(): MetodoPago
    {
        return MetodoPago::tryFrom($this->metodo_pago_predeterminado ?? '') ?? MetodoPago::Efectivo;
    }

    /**
     * Get the public URL of the logo, versioned to avoid stale browser caches.
     */
    public function logoUrl(): ?string
    {
        return $this->versionedStorageUrl($this->logo);
    }

    /**
     * Get the versioned favicon URL so logo changes are reflected immediately.
     */
    public function faviconUrl(): string
    {
        $disk = Storage::disk('public');
        $version = app(FaviconGenerator::class)->version();

        if ($version !== null) {
            return $disk->url(FaviconGenerator::ARCHIVO).'?v='.md5($version);
        }

        if ($this->logo !== null && $this->logo !== '' && $disk->exists($this->logo)) {
            $version = $this->logo.'@'.$disk->lastModified($this->logo);
        }

        if ($version === null) {
            $ruta = public_path('favicon.ico');
            $version = 'default@'.(is_file($ruta) ? filemtime($ruta) : 0);
        }

        return route('favicon', ['v' => md5($version)]);
    }

    /**
     * Get the public URL of the payment QR, versioned to avoid stale browser caches.
     */
    public function qrPagoUrl(): ?string
    {
        return $this->versionedStorageUrl($this->qr_pago);
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
}
