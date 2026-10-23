<?php

namespace App\Enums;

enum Rol: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Cajero = 'cajero';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadministrador',
            self::Admin => 'Administrador',
            self::Cajero => 'Cajero',
        };
    }

    public function puedeGestionarConfiguracion(): bool
    {
        return $this === self::Superadmin || $this === self::Admin;
    }
}
