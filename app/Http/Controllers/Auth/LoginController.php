<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function create(): View
    {
        $negocio = Negocio::query()->oldest('id')->first();

        return view('auth.login', [
            'negocio' => $negocio,
            'nombreComercial' => $negocio?->nombre_comercial ?? config('app.name'),
        ]);
    }

    /**
     * Authenticate the user against the session guard.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], [
            'login' => 'usuario o correo electrónico',
            'password' => 'contraseña',
        ]);

        $remember = $request->boolean('remember');

        $login = Str::lower(trim($credentials['login']));

        $campo = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::validate([
            $campo => $login,
            'password' => $credentials['password'],
        ])) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        $user = Auth::getLastAttempted();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        if (! $user->activo) {
            throw ValidationException::withMessages([
                'login' => 'Este usuario está dado de baja. Contacta al administrador.',
            ]);
        }

        // Se autentica sin "Recordarme" hasta que acepte los términos vigentes.
        Auth::login($user, false);

        $request->session()->regenerate();

        if (! $user->haAceptadoTerminosActuales()) {
            $destino = $request->session()->pull('url.intended', route('ventas.index'));

            $request->session()->put([
                'terminos.destino' => $destino,
                'terminos.recordar' => $remember,
            ]);

            return redirect()->route('terminos.aceptar');
        }

        if ($remember) {
            Auth::login($user, true);
        }

        return redirect()->intended(route('ventas.index'));
    }

    /**
     * Log the user out of the application.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
