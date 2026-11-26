<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class ProductosController extends Controller
{
    /**
     * Show the products and categories administration page.
     */
    public function index(): View
    {
        return view('productos.index');
    }
}
