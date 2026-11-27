<?php

namespace App\Providers;

use App\Models\Negocio;
use App\Models\User;
use App\Services\FaviconGenerator;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Cache;
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
                $usuario = auth()->user();
                $negocio = $usuario?->negocio ?? Cache::remember(
                    Negocio::CACHE_FALLBACK,
                    now()->addDay(),
                    fn () => Negocio::query()->oldest('id')->first(),
                );

                $clave = 'favicon-url:'.($negocio?->id ?? 'sin-negocio')
                    .':'.($negocio?->logo ?? 'sin-logo')
                    .':'.($negocio?->updated_at?->timestamp ?? 0)
                    .':'.(app(FaviconGenerator::class)->version() ?? 'sin-favicon')
                    .':'.(is_file(public_path('favicon.svg')) ? filemtime(public_path('favicon.svg')) : 0);

                $datos = [
                    'faviconUrl' => Cache::remember(
                        $clave,
                        now()->addDay(),
                        fn () => $negocio?->faviconUrl() ?? route('favicon'),
                    ),
                ];

                if ($view->name() === 'components.layouts.app') {
                    $datos['usuarioNav'] = $usuario;
                    $datos['negocioNav'] = $usuario?->negocio;
                    $datos['cajaNav'] = once(fn () => $usuario?->cajaAbierta());
                }

                $view->with($datos);
            }
        );
    }
}
