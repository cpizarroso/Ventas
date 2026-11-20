<?php

namespace Tests\Feature;

use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FaviconTest extends TestCase
{
    use RefreshDatabase;

    private function urlFavicon(string $html): string
    {
        preg_match('/<link rel="icon" href="([^"]+)"/', $html, $coincidencias);

        return html_entity_decode($coincidencias[1] ?? '');
    }

    public function test_el_favicon_usa_el_logo_del_negocio(): void
    {
        Storage::fake('public');

        $ruta = UploadedFile::fake()->image('logo.png', 64, 64)->store('logos', 'public');
        Negocio::factory()->create(['logo' => $ruta]);

        $respuesta = $this->get(route('favicon'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->assertStringContainsString('no-store', (string) $respuesta->baseResponse->headers->get('Cache-Control'));
        $this->assertSame(Storage::disk('public')->get($ruta), $respuesta->streamedContent());
    }

    public function test_el_favicon_cae_al_icono_por_defecto_sin_logo(): void
    {
        Storage::fake('public');
        Negocio::factory()->create(['logo' => null]);

        $respuesta = $this->get(route('favicon'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/x-icon');

        $this->assertSame((string) file_get_contents(public_path('favicon.ico')), $respuesta->streamedContent());
    }

    public function test_la_url_del_favicon_cambia_al_cambiar_o_quitar_el_logo(): void
    {
        Storage::fake('public');

        $negocio = Negocio::factory()->create(['logo' => null]);
        $primero = $this->urlFavicon((string) $this->get(route('login'))->assertOk()->getContent());

        $logoUno = UploadedFile::fake()->image('uno.png', 64, 64)->store('logos', 'public');
        $negocio->update(['logo' => $logoUno]);
        $segundo = $this->urlFavicon((string) $this->get(route('login'))->assertOk()->getContent());

        $logoDos = UploadedFile::fake()->image('dos.png', 64, 64)->store('logos', 'public');
        $negocio->update(['logo' => $logoDos]);
        $tercero = $this->urlFavicon((string) $this->get(route('login'))->assertOk()->getContent());

        $negocio->update(['logo' => null]);
        $cuarto = $this->urlFavicon((string) $this->get(route('login'))->assertOk()->getContent());

        $this->assertNotSame($primero, $segundo);
        $this->assertNotSame($segundo, $tercero);
        $this->assertNotSame($tercero, $cuarto);
    }

    public function test_todas_las_vistas_principales_incluyen_el_favicon(): void
    {
        Storage::fake('public');

        $negocio = Negocio::factory()->create(['logo' => null]);
        $admin = User::factory()->admin()->create(['negocio_id' => $negocio->id]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="icon"', false);

        $this->get(route('terminos.index'))
            ->assertOk()
            ->assertSee('rel="icon"', false);

        $this->get('/no-existe')
            ->assertNotFound()
            ->assertSee('rel="icon"', false);

        $this->actingAs($admin)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertSee('rel="icon"', false);
    }
}
