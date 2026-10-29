<?php

use App\Http\Controllers\AceptacionesTerminosController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TerminosController;
use App\Http\Controllers\VentasController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/ventas');

Route::get('/terminos', [TerminosController::class, 'index'])->name('terminos.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'terminos.aceptados'])->group(function () {
    Route::get('/ventas', [VentasController::class, 'index'])->name('ventas.index');

    Route::get('/ventas/{venta}/ticket', [VentasController::class, 'ticket'])->name('ventas.ticket');

    Route::get('/configuracion', [ConfiguracionController::class, 'index'])
        ->middleware('can:gestionar-configuracion')
        ->name('configuracion.index');

    Route::get('/configuracion/aceptaciones', [AceptacionesTerminosController::class, 'index'])
        ->middleware('can:gestionar-configuracion')
        ->name('configuracion.aceptaciones.index');

    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('can:ver-reportes')
        ->name('reportes.index');

    Route::get('/reportes/exportar', [ReporteController::class, 'exportar'])
        ->middleware('can:ver-reportes')
        ->name('reportes.export');
});
