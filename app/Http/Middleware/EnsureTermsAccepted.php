<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTermsAccepted
{
    /**
     * Redirect authenticated users who have not accepted the current terms.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->haAceptadoTerminosActuales()) {
            return redirect()->guest(route('terminos.aceptar'));
        }

        return $next($request);
    }
}
