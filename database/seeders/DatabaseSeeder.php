<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Idempotente. NO crea categorías: cada negocio debe crearlas
     * manualmente desde la pantalla de configuración.
     */
    public function run(): void
    {
        $this->call(SuperadminSeeder::class);

        $negocio = Negocio::firstOrCreate(
            ['nombre_comercial' => 'Mi Negocio'],
            ['eslogan' => null, 'logo' => null]
        );

        User::firstOrCreate(
            ['email' => 'admin@jibas.test'],
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'rol' => Rol::Admin,
                'negocio_id' => $negocio->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cajero@jibas.test'],
            [
                'name' => 'Cajero',
                'username' => 'cajero',
                'password' => Hash::make('password'),
                'rol' => Rol::Cajero,
                'negocio_id' => $negocio->id,
            ]
        );
    }
}
