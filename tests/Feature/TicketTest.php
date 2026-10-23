<?php

namespace Tests\Feature;

use App\Enums\MetodoPago;
use App\Models\Negocio;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function ventaDe(
        Negocio $negocio,
        User $vendedor,
        MetodoPago $metodo = MetodoPago::Efectivo,
        ?string $montoRecibido = null,
        ?string $cambio = null,
    ): Venta {
        $venta = Venta::factory()->create([
            'negocio_id' => $negocio->id,
            'user_id' => $vendedor->id,
            'total' => 20,
            'metodo_pago' => $metodo,
            'monto_recibido' => $montoRecibido,
            'cambio' => $cambio,
            'created_at' => '2026-10-22 10:30:00',
            'updated_at' => '2026-10-22 10:30:00',
        ]);

        $venta->detalles()->create([
            'nombre' => 'Café americano',
            'precio_unitario' => 10,
            'cantidad' => 2,
            'subtotal' => 20,
        ]);

        return $venta;
    }

    public function test_un_admin_puede_ver_el_ticket_de_una_venta_de_su_negocio(): void
    {
        $negocio = Negocio::factory()->create([
            'nombre_comercial' => 'Café Central',
            'eslogan' => 'Sabor de casa',
        ]);
        $admin = $this->adminDe($negocio);
        $venta = $this->ventaDe($negocio, $admin, MetodoPago::Efectivo, '50.00', '30.00');

        $this->actingAs($admin)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertSee('Café Central')
            ->assertSee('Sabor de casa')
            ->assertSee('#'.$venta->id)
            ->assertSee('22/10/2026 10:30')
            ->assertSee($admin->name)
            ->assertSee('Café americano')
            ->assertSee('2 x Bs 10.00')
            ->assertSee('Bs 20.00')
            ->assertSee('Bs 50.00')
            ->assertSee('Bs 30.00')
            ->assertSee('Efectivo')
            ->assertSee('Sistema desarrollado por SofCruz')
            ->assertSee('Contacto: 69175481')
            ->assertSee('size: 80mm auto', false)
            ->assertSee('@page', false);
    }

    public function test_un_cajero_del_mismo_negocio_puede_ver_el_ticket(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
        $venta = $this->ventaDe($negocio, $cajero);

        $this->actingAs($cajero)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertSee('Café americano')
            ->assertSee($cajero->name);
    }

    public function test_el_ticket_de_qr_no_muestra_recibido_ni_cambio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $venta = $this->ventaDe($negocio, $admin, MetodoPago::Qr, '20.00', '0.00');

        $this->actingAs($admin)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertSee('QR')
            ->assertSee('Pagado por QR')
            ->assertDontSee('Recibido')
            ->assertDontSee('Cambio');
    }

    public function test_no_se_puede_ver_el_ticket_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $adminAjeno = $this->adminDe($negocioAjeno);
        $ventaAjena = $this->ventaDe($negocioAjeno, $adminAjeno);

        $this->actingAs($admin)
            ->get(route('ventas.ticket', $ventaAjena))
            ->assertNotFound();

        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)
            ->get(route('ventas.ticket', $ventaAjena))
            ->assertNotFound();
    }

    public function test_un_invitado_es_redirigido_al_login_para_ver_el_ticket(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $venta = $this->ventaDe($negocio, $admin);

        $this->get(route('ventas.ticket', $venta))
            ->assertRedirect(route('login'));
    }

    public function test_el_ticket_solo_intenta_imprimir_automaticamente_cuando_se_solicita(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $venta = $this->ventaDe($negocio, $admin);

        $this->actingAs($admin)
            ->get(route('ventas.ticket', $venta))
            ->assertOk()
            ->assertDontSee("window.addEventListener('load'", false);

        $this->actingAs($admin)
            ->get(route('ventas.ticket', ['venta' => $venta, 'imprimir' => 1]))
            ->assertOk()
            ->assertSee("window.addEventListener('load'", false);
    }
}
