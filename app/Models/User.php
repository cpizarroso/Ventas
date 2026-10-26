<?php

namespace App\Models;

use App\Enums\Rol;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'cedula', 'telefono', 'direccion', 'email', 'password', 'rol', 'negocio_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => Rol::class,
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function aceptacionesTerminos(): HasMany
    {
        return $this->hasMany(AceptacionTerminos::class);
    }

    public function haAceptadoTerminosActuales(): bool
    {
        return $this->aceptacionesTerminos()
            ->where('version', config('jibas.terminos.version'))
            ->exists();
    }

    public function esSuperadmin(): bool
    {
        return $this->rol === Rol::Superadmin;
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }

    public function esCajero(): bool
    {
        return $this->rol === Rol::Cajero;
    }

    public function puedeGestionarConfiguracion(): bool
    {
        return $this->rol?->puedeGestionarConfiguracion() ?? false;
    }

    public function puedeVerReportes(): bool
    {
        return $this->rol?->puedeVerReportes() ?? false;
    }
}
