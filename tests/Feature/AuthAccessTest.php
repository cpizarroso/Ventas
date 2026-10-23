<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_rutas_protegiden_redirigen_al_login(): void
    {
        $this->get(route('ventas.index'))->assertRedirect(route('login'));
        $this->get(route('configuracion.index'))->assertRedirect(route('login'));
    }

    public function test_el_login_y_las_paginas_autenticadas_muestran_el_copyright(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('SofCruz. Todos los derechos reservados.');

        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('SofCruz. Todos los derechos reservados.');
    }

    public function test_un_usuario_puede_iniciar_sesion_con_credenciales_validas(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create([
            'negocio_id' => $negocio->id,
            'email' => 'admin@test.com',
            'password' => 'secret-password',
        ]);

        $this->post(route('login.store'), [
            'login' => 'admin@test.com',
            'password' => 'secret-password',
        ])->assertRedirect(route('ventas.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_un_usuario_puede_iniciar_sesion_con_su_nombre_de_usuario(): void
    {
        $negocio = Negocio::factory()->create();
        $superadmin = User::factory()->superadmin()->create([
            'negocio_id' => null,
            'username' => 'superadmin',
            'password' => 'secret-password',
        ]);

        $this->post(route('login.store'), [
            'login' => 'superadmin',
            'password' => 'secret-password',
        ])->assertRedirect(route('ventas.index'));

        $this->assertAuthenticatedAs($superadmin);
    }

    public function test_un_usuario_no_puede_iniciar_sesion_con_credenciales_invalidas(): void
    {
        User::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'secret-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'login' => 'admin@test.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_un_usuario_puede_cerrar_sesion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_un_cajero_no_puede_acceder_a_la_configuracion(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        $this->actingAs($cajero)
            ->get(route('configuracion.index'))
            ->assertForbidden();
    }

    public function test_un_admin_puede_acceder_a_la_configuracion(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);

        $this->actingAs($admin)
            ->get(route('configuracion.index'))
            ->assertOk()
            ->assertSee('Volver a ventas');
    }

    public function test_un_superadmin_puede_acceder_a_la_configuracion(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)
            ->get(route('configuracion.index'))
            ->assertOk();
    }

    public function test_las_categorias_de_un_negocio_no_aparecen_en_la_pantalla_de_otro_negocio(): void
    {
        $negocioA = Negocio::factory()->create();
        $negocioB = Negocio::factory()->create();
        $adminA = User::factory()->admin()->create(['negocio_id' => $negocioA->id]);
        User::factory()->admin()->create(['negocio_id' => $negocioB->id]);
        Categoria::factory()->create(['negocio_id' => $negocioA->id, 'nombre' => 'SoloDelNegocioA']);

        $this->actingAs($adminA)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('SoloDelNegocioA');
    }
}
