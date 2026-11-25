<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Send users without the current terms acceptance to the mandatory modal.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->haAceptadoTerminosActuales()) {
            $destino = $request->isMethod('GET')
                ? $request->getRequestUri()
                : route('ventas.index');

            $request->session()->put('terminos.destino', $destino);

            return redirect()
                ->route('terminos.aceptar')
                ->with('aviso_terminos', 'Debes revisar y aceptar los términos vigentes para continuar.');
        }

        return $next($request);
    }
}
