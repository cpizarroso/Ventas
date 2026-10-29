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
            ->assertSee('Versión 1.0')
            ->assertDontSee('Ir a aceptar');
    }

    public function test_el_login_muestra_el_enlace_y_el_modal_sin_el_checkbox_inicial(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Ver términos y condiciones')
            ->assertSee('modal-terminos', false)
            ->assertDontSee('name="aceptar_terminos"', false);
    }

    public function test_el_login_permite_mostrar_y_ocultar_la_contrasena(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-toggle-password="password"', false)
            ->assertSee('Mostrar contraseña')
            ->assertSee('data-password-icon-show', false)
            ->assertSee('data-password-icon-hide', false);
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

    public function test_un_usuario_que_ya_acepto_inicia_sesion_sin_aceptar_de_nuevo(): void
    {
        $user = User::factory()->create([
            'email' => 'aceptado@test.com',
            'password' => 'secret-password',
        ]);

        $this->post(route('login.store'), [
            'login' => 'aceptado@test.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('ventas.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $user->aceptacionesTerminos()->count());
    }

    public function test_un_usuario_sin_aceptacion_debe_aceptar_los_terminos_para_iniciar_sesion(): void
    {
        $this->freezeTime();

        $user = User::factory()->create([
            'email' => 'pendiente@test.com',
            'password' => 'secret-password',
        ]);
        $user->aceptacionesTerminos()->delete();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => 'pendiente@test.com',
                'password' => 'secret-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('aceptar_terminos')
            ->assertSessionHas('requiere_aceptar_terminos', true);

        $this->assertGuest();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="aceptar_terminos"', false);

        $this->post(route('login.store'), [
            'login' => 'pendiente@test.com',
            'password' => 'secret-password',
            'aceptar_terminos' => '1',
        ])->assertRedirect(route('ventas.index'));

        $this->assertAuthenticatedAs($user);

        $aceptacion = $user->aceptacionesTerminos()->sole();

        $this->assertSame(config('jibas.terminos.version'), $aceptacion->version);
        $this->assertSame(now()->toDateTimeString(), $aceptacion->aceptado_en->toDateTimeString());
        $this->assertNotNull($aceptacion->ip_address);
        $this->assertNotNull($aceptacion->user_agent);
    }

    public function test_el_login_redirige_a_ventas_aunque_exista_un_destino_previo(): void
    {
        User::factory()->create([
            'email' => 'aceptado@test.com',
            'password' => 'secret-password',
        ]);

        $this->withSession(['url.intended' => route('reportes.index')]);

        $this->post(route('login.store'), [
            'login' => 'aceptado@test.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('ventas.index'));
    }

    public function test_un_usuario_con_la_version_vigente_puede_acceder_a_sus_pantallas(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertOk();
    }

    public function test_un_usuario_con_una_aceptacion_vencida_es_deslogueado_y_enviado_al_login(): void
    {
        $user = User::factory()->create();
        $user->aceptacionesTerminos()->update(['version' => '0.9']);

        $this->actingAs($user)
            ->get(route('ventas.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_un_usuario_puede_volver_a_entrar_despues_de_aceptar_la_nueva_version(): void
    {
        $user = User::factory()->create([
            'email' => 'version@test.com',
            'password' => 'secret-password',
        ]);
        $user->aceptacionesTerminos()->update(['version' => '0.9']);

        $this->post(route('login.store'), [
            'login' => 'version@test.com',
            'password' => 'secret-password',
            'aceptar_terminos' => '1',
        ])->assertRedirect(route('ventas.index'));

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('aceptaciones_terminos', [
            'user_id' => $user->id,
            'version' => config('jibas.terminos.version'),
        ]);

        $this->assertSame(2, $user->aceptacionesTerminos()->count());
    }

    public function test_un_invitado_no_puede_acceder_al_registro_de_aceptaciones(): void
    {
        $this->get(route('configuracion.aceptaciones.index'))
            ->assertRedirect(route('login'));
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
