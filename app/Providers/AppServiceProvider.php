<?php

namespace App\Providers;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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

        View::composer(
            ['components.layouts.app', 'components.layouts.legal', 'components.error-page', 'auth.login', 'errors.*'],
            function (ViewContract $view): void {
                $negocio = Negocio::query()->oldest('id')->first();

                $view->with('faviconUrl', $negocio?->faviconUrl() ?? route('favicon'));
            }
        );
    }
}
