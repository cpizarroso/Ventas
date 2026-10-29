<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\RegistroAceptacionesTerminos;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AceptacionesTerminosAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_administrador_solo_ve_las_aceptaciones_de_su_negocio(): void
    {
        $negocioA = Negocio::factory()->create();
        $negocioB = Negocio::factory()->create();

        $admin = User::factory()->admin()->create(['negocio_id' => $negocioA->id]);
        User::factory()->create(['negocio_id' => $negocioA->id, 'name' => 'Aceptado Negocio A']);
        User::factory()->create(['negocio_id' => $negocioB->id, 'name' => 'Aceptado Negocio B']);

        $this->actingAs($admin)
            ->get(route('configuracion.aceptaciones.index'))
            ->assertOk()
            ->assertSee('Aceptado Negocio A')
            ->assertDontSee('Aceptado Negocio B');
    }

    public function test_un_superadmin_ve_las_aceptaciones_de_todos_los_negocios(): void
    {
        $negocioA = Negocio::factory()->create(['nombre_comercial' => 'Negocio A']);
        $negocioB = Negocio::factory()->create(['nombre_comercial' => 'Negocio B']);

        $superadmin = User::factory()->superadmin()->create();
        User::factory()->create(['negocio_id' => $negocioA->id, 'name' => 'Aceptado Negocio A']);
        User::factory()->create(['negocio_id' => $negocioB->id, 'name' => 'Aceptado Negocio B']);

        $this->actingAs($superadmin)
            ->get(route('configuracion.aceptaciones.index'))
            ->assertOk()
            ->assertSee('Aceptado Negocio A')
            ->assertSee('Aceptado Negocio B')
            ->assertSee('Negocio A')
            ->assertSee('Negocio B');
    }

    public function test_un_cajero_no_puede_ver_el_registro_de_aceptaciones(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        $this->actingAs($cajero)
            ->get(route('configuracion.aceptaciones.index'))
            ->assertForbidden();
    }

    public function test_el_administrador_puede_buscar_aceptaciones_por_nombre(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);
        User::factory()->create(['negocio_id' => $negocio->id, 'name' => 'Ana Buscada']);
        User::factory()->create(['negocio_id' => $negocio->id, 'name' => 'Luis Otro']);

        Livewire::actingAs($admin)
            ->test(RegistroAceptacionesTerminos::class)
            ->set('buscar', 'Ana Buscada')
            ->assertSee('Ana Buscada')
            ->assertDontSee('Luis Otro');
    }
}
