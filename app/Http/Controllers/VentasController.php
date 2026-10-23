<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VentasController extends Controller
{
    /**
     * Main sales screen: entry point to the business sale categories.
     */
    public function index(): View|RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user !== null, 401);

        return view('ventas.index');
    }

    /**
     * Printable thermal receipt for a sale of the authenticated user's business.
     */
    public function ticket(Venta $venta): View
    {
        $negocioId = Auth::user()?->negocio_id;

        abort_if($negocioId === null || $venta->negocio_id !== $negocioId, 404);

        $venta->load(['negocio', 'user', 'detalles']);

        return view('ventas.ticket', [
            'venta' => $venta,
        ]);
    }
}
