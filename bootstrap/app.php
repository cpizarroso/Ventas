<?php

use App\Http\Middleware\EnsureTermsAccepted;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'terminos.aceptados' => EnsureTermsAccepted::class,
        ]);

        // El aviso de caja se escribe desde JS, por lo que no debe cifrarse.
        $middleware->encryptCookies(except: ['aviso_caja_cerrado']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Las personalizaciones específicas de Hostinger se cargan desde un archivo
// ignorado por Git, así no se pierden al actualizar la rama main.
if (file_exists(__DIR__.'/app.hostinger.php')) {
    require __DIR__.'/app.hostinger.php';
}

return $app;
