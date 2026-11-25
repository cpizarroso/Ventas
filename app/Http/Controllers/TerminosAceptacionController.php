<?php

namespace App\Http\Controllers;

use App\Models\AceptacionTerminos;
use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TerminosAceptacionController extends Controller
{
    /**
     * Show the mandatory acceptance screen with the full terms document.
     */
    public function show(): View
    {
        $negocio = Negocio::query()->oldest('id')->first();

        return view('terminos.aceptar', [
            'negocio' => $negocio,
            'nombreComercial' => $negocio?->nombre_comercial ?? config('app.name'),
        ]);
    }

    /**
     * Record acceptance of the current terms version and continue to the app.
     */
    public function aceptar(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user === null, 403, 'Debes iniciar sesión para aceptar los términos.');
        abort_if(! $user->activo, 403, 'Este usuario está dado de baja.');

        AceptacionTerminos::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'version' => config('jibas.terminos.version'),
            ],
            [
                'aceptado_en' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        if ($request->session()->pull('terminos.recordar', false)) {
            Auth::login($user, true);
        }

        $request->session()->regenerate();

        return redirect()->to($this->destinoSeguro($request));
    }

    /**
     * Reject the terms and end the session.
     */
    public function rechazar(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('aviso_terminos', 'No aceptaste los términos vigentes, por eso se cerró tu sesión. Debes aceptarlos para continuar.');
    }

    /**
     * Resolve a safe internal destination after acceptance.
     */
    private function destinoSeguro(Request $request): string
    {
        $destino = $request->session()->pull('terminos.destino');

        if (! is_string($destino) || $destino === '' || str_starts_with($destino, '//')) {
            return route('ventas.index');
        }

        $url = str_starts_with($destino, '/') ? url($destino) : $destino;

        return str_starts_with($url, url('/')) ? $destino : route('ventas.index');
    }
}
