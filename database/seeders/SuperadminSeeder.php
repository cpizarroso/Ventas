<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\AceptacionTerminos;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Crea los usuarios base del negocio de forma idempotente.
     */
    public function run(): void
    {
        User::query()
            ->whereIn('email', [
                'superadmin@jibas.test',
                'cajero@jibas.test',
                'cajero-reporte@jibas.test',
            ])
            ->delete();

        $negocio = Negocio::query()->oldest('id')->first()
            ?? Negocio::firstOrCreate(
                ['nombre_comercial' => 'Mi Negocio'],
                ['eslogan' => null, 'logo' => null]
            );

        foreach ($this->usuarios() as $usuario) {
            $nuevoUsuario = User::firstOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'username' => $usuario['username'],
                    'password' => Hash::make('password'),
                    'rol' => $usuario['rol'],
                    'negocio_id' => $negocio->id,
                ]
            );

            AceptacionTerminos::firstOrCreate(
                [
                    'user_id' => $nuevoUsuario->id,
                    'version' => config('jibas.terminos.version'),
                ],
                [
                    'aceptado_en' => now(),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Seeder',
                ]
            );
        }
    }

    /**
     * @return array<int, array{email: string, name: string, username: string, rol: Rol}>
     */
    private function usuarios(): array
    {
        return [
            [
                'email' => 'admin@jibas.test',
                'name' => 'Administrador',
                'username' => 'admin',
                'rol' => Rol::Admin,
            ],
            [
                'email' => 'cajero1@jibas.test',
                'name' => 'Cajero 1',
                'username' => 'cajero1',
                'rol' => Rol::Cajero,
            ],
            [
                'email' => 'cajero2@jibas.test',
                'name' => 'Cajero 2',
                'username' => 'cajero2',
                'rol' => Rol::Cajero,
            ],
            [
                'email' => 'jpbalcazar@jibas.test',
                'name' => 'Juan Pablo Balcazar',
                'username' => 'jpbalcazar',
                'rol' => Rol::Admin,
            ],
        ];
    }
}
