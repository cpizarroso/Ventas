<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\View\View;

class TerminosController extends Controller
{
    public function index(): View
    {
        return view('terminos.index', $this->datosLegales());
    }

    /**
     * @return array{
     *     negocio: Negocio|null,
     *     nombreComercial: string,
     *     titular: string,
     *     contacto: string,
     *     ciudad: string,
     *     pais: string,
     *     version: string,
     *     vigenteDesde: string
     * }
     */
    private function datosLegales(): array
    {
        $negocio = Negocio::query()->oldest('id')->first();

        return [
            'negocio' => $negocio,
            'nombreComercial' => $negocio?->nombre_comercial ?? config('app.name'),
            'titular' => config('jibas.legal.titular'),
            'contacto' => config('jibas.legal.contacto'),
            'ciudad' => config('jibas.legal.ciudad'),
            'pais' => config('jibas.legal.pais'),
            'version' => config('jibas.terminos.version'),
            'vigenteDesde' => config('jibas.terminos.vigente_desde'),
        ];
    }
}
