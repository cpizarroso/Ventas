<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TerminosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_de_terminos_es_publica_y_muestra_el_contenido_legal(): void
    {
        $this->get(route('terminos.index'))
            ->assertOk()
            ->assertSee('Términos y Condiciones y Política de Privacidad')
            ->assertSee('vibe coding')
            ->assertSee('inteligencia artificial')
            ->assertSee('imágenes y contenidos')
            ->assertSee('SofCruz')
            ->assertSee('agenciasofcruz@gmail.com')
            ->assertSee('Santa Cruz de la Sierra')
            ->assertSee('Versión 1.0');
    }

    public function test_el_login_y_el_footer_muestran_el_enlace_a_los_terminos(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('terminos.index'), false)
            ->assertSee('Términos y privacidad');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee(route('terminos.index'), false)
            ->assertSee('Términos y privacidad');
    }

    public function test_un_usuario_sin_aceptacion_es_redirigido_a_los_terminos(): void
    {
        $user = User::factory()->create();
        $user->aceptacionesTerminos()->delete();

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertRedirect(route('terminos.aceptar'));

        $this->actingAs($user)
            ->get(route('reportes.index'))
            ->assertRedirect(route('terminos.aceptar'));
    }

    public function test_la_pantalla_de_aceptacion_muestra_el_documento_y_la_casilla(): void
    {
        $user = User::factory()->create();
        $user->aceptacionesTerminos()->delete();

        $this->actingAs($user)
            ->get(route('terminos.index'))
            ->assertOk()
            ->assertSee('Ir a aceptar');

        $this->actingAs($user)
            ->get(route('terminos.aceptar'))
            ->assertOk()
            ->assertSee('Aceptar y continuar')
            ->assertSee('name="aceptar"', false)
            ->assertSee('Versión 1.0');
    }

    public function test_un_usuario_puede_aceptar_los_terminos_y_acceder_a_ventas(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $user->aceptacionesTerminos()->delete();

        $this->actingAs($user)
            ->post(route('terminos.store'), ['aceptar' => '1'])
            ->assertRedirect(route('ventas.index'));

        $aceptacion = $user->aceptacionesTerminos()->sole();

        $this->assertSame('1.0', $aceptacion->version);
        $this->assertSame(now()->toDateTimeString(), $aceptacion->aceptado_en->toDateTimeString());
        $this->assertNotNull($aceptacion->ip_address);
        $this->assertNotNull($aceptacion->user_agent);

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertOk();
    }

    public function test_no_se_puede_aceptar_sin_marcar_la_casilla(): void
    {
        $user = User::factory()->create();
        $user->aceptacionesTerminos()->delete();

        $this->actingAs($user)
            ->from(route('terminos.aceptar'))
            ->post(route('terminos.store'))
            ->assertRedirect(route('terminos.aceptar'))
            ->assertSessionHasErrors('aceptar');

        $this->assertDatabaseCount('aceptaciones_terminos', 0);
    }

    public function test_un_invitado_no_puede_aceptar_los_terminos(): void
    {
        $this->post(route('terminos.store'), ['aceptar' => '1'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('aceptaciones_terminos', 0);
    }

    public function test_una_aceptacion_de_una_version_anterior_obliga_a_aceptar_nuevamente(): void
    {
        $user = User::factory()->create();
        $user->aceptacionesTerminos()->update(['version' => '0.9']);

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertRedirect(route('terminos.aceptar'));

        $this->actingAs($user)
            ->post(route('terminos.store'), ['aceptar' => '1'])
            ->assertRedirect(route('ventas.index'));

        $this->assertDatabaseHas('aceptaciones_terminos', [
            'user_id' => $user->id,
            'version' => '1.0',
        ]);

        $this->assertSame(2, $user->aceptacionesTerminos()->count());
    }

    public function test_un_usuario_con_la_version_vigente_aceptada_no_ve_la_pantalla_de_aceptacion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('terminos.aceptar'))
            ->assertRedirect(route('ventas.index'));
    }

    public function test_el_logout_sigue_disponible_para_un_usuario_pendiente(): void
    {
        $user = User::factory()->create();
        $user->aceptacionesTerminos()->delete();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
