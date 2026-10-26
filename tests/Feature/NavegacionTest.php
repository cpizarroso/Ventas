<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_opcion_del_navbar_solo_se_marca_activa_en_su_ruta(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);

        foreach ([
            'ventas.index' => 'Ventas',
            'reportes.index' => 'Reportes',
            'configuracion.index' => 'Configuración',
        ] as $ruta => $etiqueta) {
            $respuesta = $this->actingAs($admin)->get(route($ruta))->assertOk();

            $this->assertSame(1, substr_count($respuesta->getContent(), 'aria-current="page"'));
            $this->assertStringContainsString('href="'.route($ruta).'"', $respuesta->getContent());
            $this->assertStringContainsString($etiqueta, $respuesta->getContent());
        }
    }
}
