<?php

namespace Tests\Feature;

use App\Models\Negocio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_login_muestra_el_nombre_y_logo_del_negocio_configurado(): void
    {
        Negocio::factory()->create([
            'nombre_comercial' => 'JOTAPE',
            'logo' => 'logos/logo.png',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('JOTAPE')
            ->assertSee('Iniciar sesión · JOTAPE')
            ->assertSee('Logo de JOTAPE')
            ->assertSee('storage/logos/logo.png', false);
    }

    public function test_el_login_muestra_el_icono_predeterminado_cuando_el_negocio_no_tiene_logo(): void
    {
        Negocio::factory()->create([
            'nombre_comercial' => 'Sin Logo',
            'logo' => null,
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sin Logo')
            ->assertDontSee('storage/logos', false)
            ->assertSee('M3 9l1.5-5h15L21 9', false);
    }

    public function test_el_login_usa_el_nombre_de_la_aplicacion_cuando_no_hay_negocio(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('Iniciar sesión · '.config('app.name'));
    }
}
