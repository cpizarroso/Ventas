<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Livewire\Configuracion\UsuariosAdmin;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class UsuariosAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function cajeroDe(Negocio $negocio): User
    {
        return User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
    }

    /**
     * Fill the user form with valid data.
     *
     * @param  array<string, mixed>  $datos
     */
    private function conDatos(Testable $componente, array $datos = []): Testable
    {
        $datos = array_merge([
            'nombre' => 'Ana Pérez',
            'cedula' => '12345678-1P',
            'telefono' => '70012345',
            'email' => 'ana@example.com',
            'direccion' => 'Av. Principal 123',
            'usuario' => 'ana.perez',
            'rol' => Rol::Cajero->value,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ], $datos);

        foreach ($datos as $campo => $valor) {
            $componente->set($campo, $valor);
        }

        return $componente;
    }

    public function test_un_admin_puede_crear_un_cajero_con_sus_datos_de_acceso(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'usuario' => 'Ana.Perez',
            'email' => 'ANA@EXAMPLE.COM',
        ])
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('password', '')
            ->assertSet('usuario', '')
            ->assertSet('rol', Rol::Cajero->value)
            ->assertSee('Guardado exitosamente')
            ->assertDispatched('toast', message: 'Guardado exitosamente');

        $usuario = User::query()->where('username', 'ana.perez')->firstOrFail();

        $this->assertSame($negocio->id, $usuario->negocio_id);
        $this->assertSame('Ana Pérez', $usuario->name);
        $this->assertSame('12345678-1P', $usuario->cedula);
        $this->assertSame('70012345', $usuario->telefono);
        $this->assertSame('ana@example.com', $usuario->email);
        $this->assertSame('Av. Principal 123', $usuario->direccion);
        $this->assertSame(Rol::Cajero, $usuario->rol);
        $this->assertTrue(Hash::check('secret123', $usuario->password));

        $this->post(route('logout'));

        $this->post(route('login.store'), [
            'login' => 'ANA.PEREZ',
            'password' => 'secret123',
            'aceptar_terminos' => '1',
        ])->assertRedirect(route('ventas.index'));

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_un_admin_puede_crear_otro_administrador_sin_correo_ni_direccion(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'nombre' => 'Luis Admin',
            'cedula' => '87654321',
            'usuario' => 'luis.admin',
            'email' => null,
            'direccion' => null,
            'rol' => Rol::Admin->value,
        ])
            ->call('guardar')
            ->assertHasNoErrors();

        $usuario = User::query()->where('username', 'luis.admin')->firstOrFail();

        $this->assertSame(Rol::Admin, $usuario->rol);
        $this->assertNull($usuario->email);
        $this->assertNull($usuario->direccion);
    }

    public function test_los_campos_obligatorios_son_validados_al_crear(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'nombre' => '',
            'cedula' => '',
            'telefono' => '',
            'usuario' => '',
            'password' => '',
            'password_confirmation' => '',
        ])
            ->call('guardar')
            ->assertHasErrors(['nombre', 'cedula', 'telefono', 'usuario', 'password']);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_el_correo_el_telefono_y_el_usuario_validan_su_formato(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'email' => 'correo-invalido',
            'telefono' => 'abc',
            'usuario' => 'Ana Perez',
        ])
            ->call('guardar')
            ->assertHasErrors(['email', 'telefono', 'usuario']);
    }

    public function test_la_cedula_es_unica_por_negocio_y_puede_repetirse_en_otro(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'cedula' => '12345678-1P',
            'username' => 'existente',
        ]);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'usuario' => 'nuevo.usuario',
            'email' => null,
        ])
            ->call('guardar')
            ->assertHasErrors(['cedula']);

        $otroNegocio = Negocio::factory()->create();
        $otroAdmin = $this->adminDe($otroNegocio);

        $this->conDatos(Livewire::actingAs($otroAdmin)->test(UsuariosAdmin::class), [
            'usuario' => 'nuevo.usuario',
            'email' => null,
        ])
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'negocio_id' => $otroNegocio->id,
            'cedula' => '12345678-1P',
        ]);
    }

    public function test_el_usuario_y_el_correo_son_unicos_globalmente(): void
    {
        $negocio = Negocio::factory()->create();
        $otroNegocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        User::factory()->cajero()->create([
            'negocio_id' => $otroNegocio->id,
            'username' => 'usuario.usado',
            'email' => 'usado@example.com',
        ]);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'usuario' => 'usuario.usado',
            'email' => 'nuevo@example.com',
        ])
            ->call('guardar')
            ->assertHasErrors(['usuario' => 'unique']);

        $this->conDatos(Livewire::actingAs($admin)->test(UsuariosAdmin::class), [
            'usuario' => 'nuevo.usuario',
            'email' => 'usado@example.com',
        ])
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_un_admin_puede_editar_un_usuario_y_conservar_su_contrasena(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $usuario = User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'password' => 'clave-original',
            'username' => 'cajero.original',
        ]);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('iniciarEdicion', $usuario->id)
            ->set('nombre', 'Cajero Actualizado')
            ->set('usuario', 'cajero.actualizado')
            ->set('telefono', '71122334')
            ->set('rol', Rol::Admin->value)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('editandoId', null)
            ->assertSee('Guardado exitosamente')
            ->assertDispatched('toast', message: 'Guardado exitosamente');

        $usuario->refresh();

        $this->assertSame('Cajero Actualizado', $usuario->name);
        $this->assertSame('cajero.actualizado', $usuario->username);
        $this->assertSame('71122334', $usuario->telefono);
        $this->assertSame(Rol::Admin, $usuario->rol);
        $this->assertTrue(Hash::check('clave-original', $usuario->password));
    }

    public function test_un_admin_puede_cambiar_la_contrasena_de_un_usuario(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $usuario = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('iniciarEdicion', $usuario->id)
            ->set('password', 'nueva-clave')
            ->set('password_confirmation', 'nueva-clave')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nueva-clave', $usuario->fresh()->password));
    }

    public function test_un_admin_no_puede_quitarse_el_rol_de_administrador(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('iniciarEdicion', $admin->id)
            ->set('rol', Rol::Cajero->value)
            ->call('guardar')
            ->assertHasErrors(['rol']);

        $this->assertSame(Rol::Admin, $admin->fresh()->rol);
    }

    public function test_un_admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('confirmarEliminacion', $admin->id)
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function test_al_eliminar_un_usuario_sus_ventas_historicas_se_conservan(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $usuario = $this->cajeroDe($negocio);
        $venta = Venta::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $usuario->id,
            'total' => 25,
        ]);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('confirmarEliminacion', $usuario->id)
            ->assertSet('eliminandoId', $usuario->id)
            ->call('eliminar')
            ->assertSet('eliminandoId', null)
            ->assertNotDispatched('toast');

        $this->assertModelMissing($usuario);
        $this->assertModelExists($venta);
        $this->assertNull($venta->fresh()->user_id);
    }

    public function test_un_admin_no_puede_gestionar_usuarios_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $usuarioAjeno = $this->cajeroDe($negocioAjeno);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('iniciarEdicion', $usuarioAjeno->id)
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->call('confirmarEliminacion', $usuarioAjeno->id)
            ->assertNotFound();

        $this->assertModelExists($usuarioAjeno);
    }

    public function test_un_cajero_no_puede_gestionar_usuarios(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);

        Livewire::actingAs($cajero)
            ->test(UsuariosAdmin::class)
            ->assertForbidden();

        $this->assertSame(1, User::query()->count());
    }

    public function test_el_listado_solo_muestra_usuarios_del_negocio_actual(): void
    {
        $negocio = Negocio::factory()->create();
        $otroNegocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        User::factory()->cajero()->create([
            'negocio_id' => $negocio->id,
            'name' => 'Usuario Propio',
        ]);
        User::factory()->cajero()->create([
            'negocio_id' => $otroNegocio->id,
            'name' => 'Usuario Ajeno',
        ]);

        Livewire::actingAs($admin)
            ->test(UsuariosAdmin::class)
            ->assertSee('Usuario Propio')
            ->assertDontSee('Usuario Ajeno');
    }

    public function test_la_pantalla_de_configuracion_incluye_la_gestion_de_usuarios(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        $this->actingAs($admin)
            ->get(route('configuracion.index'))
            ->assertOk()
            ->assertSee('Usuarios del negocio')
            ->assertSee('Guardar cambios')
            ->assertSee('x-on:toast.window', false)
            ->assertSee('Cerrar notificación');
    }
}
