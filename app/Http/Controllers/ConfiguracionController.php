<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    /**
     * Settings screen: business identity and sale categories management.
     */
    public function index(): View
    {
        return view('configuracion.index');
    }
}
