<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ruta_inexistente_muestra_la_pagina_404_personalizada(): void
    {
        $this->get('/no-existe')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Página no encontrada');
    }

    public function test_un_cajero_ve_la_pagina_403_personalizada_sin_el_mensaje_en_ingles(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        $this->actingAs($cajero)
            ->get(route('configuracion.index'))
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Acceso denegado')
            ->assertDontSee('This action is unauthorized.');
    }

    public function test_muestra_el_mensaje_personalizado_de_la_excepcion_cuando_es_util(): void
    {
        $this->view('errors.403', [
            'exception' => new HttpException(403, 'Tu usuario no tiene un negocio asignado.'),
        ])
            ->assertSee('Acceso denegado')
            ->assertSee('Tu usuario no tiene un negocio asignado.');
    }

    public function test_no_muestra_el_mensaje_generico_de_la_excepcion(): void
    {
        $this->view('errors.403', [
            'exception' => new HttpException(403, 'This action is unauthorized.'),
        ])
            ->assertSee('Acceso denegado')
            ->assertDontSee('This action is unauthorized.');
    }

    public function test_un_invitado_ve_el_enlace_de_inicio_de_sesion_en_el_error_401(): void
    {
        $this->view('errors.401', [
            'exception' => new HttpException(401),
        ])
            ->assertSee('Iniciar sesión')
            ->assertSee('href="'.route('login').'"', false);
    }

    public function test_el_error_419_ofrece_recargar_la_pagina(): void
    {
        $this->view('errors.419', [
            'exception' => new HttpException(419),
        ])
            ->assertSee('Recargar página')
            ->assertSee('window.location.reload()', false);
    }

    public function test_un_usuario_autenticado_ve_el_enlace_de_ventas_en_el_error_403(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);

        $this->actingAs($admin)
            ->view('errors.403', [
                'exception' => new HttpException(403),
            ])
            ->assertSee('Volver a ventas')
            ->assertSee('href="'.route('ventas.index').'"', false);
    }

    #[DataProvider('paginasPersonalizadas')]
    public function test_cada_codigo_muestra_su_titulo_y_mensaje(int $codigo, string $titulo, string $mensaje): void
    {
        $this->view("errors.{$codigo}", [
            'exception' => new HttpException($codigo),
        ])
            ->assertSee((string) $codigo)
            ->assertSee($titulo)
            ->assertSee($mensaje);
    }

    public static function paginasPersonalizadas(): array
    {
        return [
            '400' => [400, 'Solicitud no válida', 'No pudimos entender los datos enviados. Revisa el formulario e inténtalo de nuevo.'],
            '401' => [401, 'Inicio de sesión requerido', 'Necesitas iniciar sesión para continuar.'],
            '402' => [402, 'Pago requerido', 'Esta acción requiere una suscripción o pago vigente. Comunícate con el administrador.'],
            '403' => [403, 'Acceso denegado', 'No tienes permiso para realizar esta acción. Si crees que es un error, comunícate con el administrador.'],
            '404' => [404, 'Página no encontrada', 'La página o el recurso que buscas no existe o fue movido.'],
            '419' => [419, 'Sesión caducada', 'Tu sesión de seguridad expiró. Recarga la página y vuelve a intentarlo.'],
            '429' => [429, 'Demasiadas solicitudes', 'Has realizado demasiados intentos. Espera un momento y vuelve a intentarlo.'],
            '500' => [500, 'Error del servidor', 'Ocurrió un problema inesperado. Ya fuimos notificados; inténtalo de nuevo en unos minutos.'],
            '503' => [503, 'Servicio no disponible', 'El servicio está en mantenimiento o no está disponible temporalmente. Vuelve a intentarlo más tarde.'],
        ];
    }

    #[DataProvider('codigosSinVista')]
    public function test_los_codigos_sin_vista_usan_el_fallback_correspondiente(int $codigo, string $vista, string $titulo): void
    {
        $this->view("errors.{$vista}", [
            'exception' => new HttpException($codigo),
        ])
            ->assertSee((string) $codigo)
            ->assertSee($titulo);
    }

    public static function codigosSinVista(): array
    {
        return [
            '418' => [418, '4xx', 'No pudimos completar la solicitud'],
            '502' => [502, '5xx', 'No pudimos atender tu solicitud'],
        ];
    }
}
