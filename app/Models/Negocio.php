<?php

namespace App\Models;

use Database\Factories\NegocioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['nombre_comercial', 'eslogan', 'logo', 'qr_pago'])]
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

    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class);
    }

    /**
     * Get the public URL of the logo, versioned to avoid stale browser caches.
     */
    public function logoUrl(): ?string
    {
        return $this->versionedStorageUrl($this->logo);
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
