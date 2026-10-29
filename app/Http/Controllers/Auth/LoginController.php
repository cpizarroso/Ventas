<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AceptacionTerminos;
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

        if (! $user->haAceptadoTerminosActuales()) {
            if (! $request->boolean('aceptar_terminos')) {
                return redirect()
                    ->route('login')
                    ->withInput($request->only('login', 'remember'))
                    ->with('requiere_aceptar_terminos', true)
                    ->withErrors([
                        'aceptar_terminos' => 'Debes aceptar los Términos y Condiciones y la Política de Privacidad vigentes para iniciar sesión.',
                    ]);
            }

            $this->registrarAceptacion($request, $user);
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()->route('ventas.index');
    }

    /**
     * Record the acceptance of the current terms version for the user.
     */
    private function registrarAceptacion(Request $request, User $user): void
    {
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
