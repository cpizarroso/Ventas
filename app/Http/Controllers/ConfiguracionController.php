<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    /**
     * Settings screen: business identity, users, sale preferences and default images.
     */
    public function index(): View
    {
        return view('configuracion.index');
    }
}
