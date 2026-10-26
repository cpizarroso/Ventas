<?php

namespace App\Http\Controllers;

use App\Models\AceptacionTerminos;
use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TerminosController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('terminos.index', [
            ...$this->datosLegales(),
            'requiereAceptacion' => $user !== null && ! $user->haAceptadoTerminosActuales(),
        ]);
    }

    public function aceptar(Request $request): View|RedirectResponse
    {
        if ($request->user()?->haAceptadoTerminosActuales()) {
            return redirect()->route('ventas.index');
        }

        return view('terminos.aceptar', $this->datosLegales());
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'aceptar' => ['accepted'],
        ], [
            'aceptar.accepted' => 'Debes aceptar los términos y la política de privacidad para continuar.',
        ]);

        $user = $request->user();

        abort_unless($user !== null, 401);

        if ($user->haAceptadoTerminosActuales()) {
            return redirect()->route('ventas.index');
        }

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

        return redirect()
            ->intended(route('ventas.index'))
            ->with('estado', 'Términos y política de privacidad aceptados.');
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
