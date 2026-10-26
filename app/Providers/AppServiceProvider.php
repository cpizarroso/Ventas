<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('gestionar-configuracion', function (User $user): bool {
            return $user->puedeGestionarConfiguracion();
        });

        Gate::define('ver-reportes', function (User $user): bool {
            return $user->puedeVerReportes();
        });
    }
}
