<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Create the superadmin account if it does not exist yet (idempotent).
     *
     * NO se crean categorías aquí ni en ningún otro seeder.
     */
    public function run(): void
    {
        $email = config('jibas.superadmin.email');
        $username = config('jibas.superadmin.username');

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $username,
                'username' => $username,
                'password' => Hash::make(config('jibas.superadmin.password')),
                'rol' => Rol::Superadmin,
                'negocio_id' => null,
            ]
        );
    }
}
