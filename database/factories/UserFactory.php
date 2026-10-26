<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\AceptacionTerminos;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'cedula' => fake()->unique()->numerify('#######'),
            'telefono' => fake()->numerify('7#######'),
            'direccion' => fake()->address(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rol' => Rol::Cajero,
            'negocio_id' => Negocio::factory(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            AceptacionTerminos::factory()->create([
                'user_id' => $user->id,
                'version' => config('jibas.terminos.version'),
            ]);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Admin,
        ]);
    }

    public function cajero(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Cajero,
        ]);
    }

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'rol' => Rol::Superadmin,
            'negocio_id' => null,
        ]);
    }
}
