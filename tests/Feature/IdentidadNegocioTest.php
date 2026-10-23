<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\IdentidadNegocio;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class IdentidadNegocioTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    public function test_un_admin_puede_actualizar_el_nombre_comercial_y_el_eslogan(): void
    {
        $negocio = Negocio::factory()->create([
            'nombre_comercial' => 'Nombre Viejo',
            'eslogan' => null,
        ]);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('nombre_comercial', 'Nombre Nuevo')
            ->set('eslogan', 'El mejor sabor')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('negocios', [
            'id' => $negocio->id,
            'nombre_comercial' => 'Nombre Nuevo',
            'eslogan' => 'El mejor sabor',
        ]);
    }

    public function test_el_eslogan_puede_dejarse_vacio(): void
    {
        $negocio = Negocio::factory()->create(['eslogan' => 'Anterior']);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('nombre_comercial', 'Mi negocio')
            ->set('eslogan', '')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('negocios', [
            'id' => $negocio->id,
            'eslogan' => null,
        ]);
    }

    public function test_el_nombre_comercial_es_obligatorio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('nombre_comercial', '')
            ->call('guardar')
            ->assertHasErrors(['nombre_comercial' => 'required']);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('nombre_comercial', 'a')
            ->call('guardar')
            ->assertHasErrors(['nombre_comercial' => 'min']);
    }

    public function test_un_admin_puede_subir_un_logo_valido(): void
    {
        Storage::fake('public');
        $negocio = Negocio::factory()->create(['logo' => null]);
        $admin = $this->adminDe($negocio);
        $archivo = UploadedFile::fake()->image('logo.png', 200, 200);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('logo_nuevo', $archivo)
            ->call('guardar')
            ->assertHasNoErrors();

        $negocio->refresh();
        $this->assertNotNull($negocio->logo);
        Storage::disk('public')->assertExists($negocio->logo);
    }

    public function test_se_rechaza_un_logo_con_formato_no_permitido(): void
    {
        Storage::fake('public');
        $negocio = Negocio::factory()->create(['logo' => null]);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('logo_nuevo', UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'))
            ->call('guardar')
            ->assertHasErrors(['logo_nuevo']);

        $this->assertNull($negocio->fresh()->logo);
    }

    public function test_se_rechaza_un_logo_demasiado_grande(): void
    {
        Storage::fake('public');
        $negocio = Negocio::factory()->create(['logo' => null]);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('logo_nuevo', UploadedFile::fake()->image('logo.png', 200, 200)->size(3000))
            ->call('guardar')
            ->assertHasErrors(['logo_nuevo']);

        $this->assertNull($negocio->fresh()->logo);
    }

    public function test_un_admin_puede_eliminar_el_logo(): void
    {
        Storage::fake('public');
        $negocio = Negocio::factory()->create();
        $negocio->update(['logo' => UploadedFile::fake()->image('logo.png')->store('logos', 'public')]);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->call('eliminarLogo')
            ->assertHasNoErrors();

        $this->assertNull($negocio->fresh()->logo);
    }

    public function test_un_admin_no_puede_modificar_la_identidad_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create(['nombre_comercial' => 'Propio']);
        $negocioAjeno = Negocio::factory()->create(['nombre_comercial' => 'Ajeno']);
        $admin = $this->adminDe($negocioPropio);

        // El componente siempre opera sobre el negocio del usuario autenticado,
        // por lo que guardar solo puede afectar al negocio propio.
        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->set('nombre_comercial', 'Modificado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('negocios', [
            'id' => $negocioPropio->id,
            'nombre_comercial' => 'Modificado',
        ]);
        $this->assertDatabaseHas('negocios', [
            'id' => $negocioAjeno->id,
            'nombre_comercial' => 'Ajeno',
        ]);
    }

    public function test_un_cajero_no_puede_modificar_la_identidad_del_negocio(): void
    {
        $negocio = Negocio::factory()->create(['nombre_comercial' => 'Original']);
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);

        Livewire::actingAs($cajero)
            ->test(IdentidadNegocio::class)
            ->set('nombre_comercial', 'Hackeado')
            ->call('guardar')
            ->assertForbidden();

        $this->assertDatabaseHas('negocios', [
            'id' => $negocio->id,
            'nombre_comercial' => 'Original',
        ]);
    }

    public function test_un_admin_puede_ver_el_logo_actual_y_su_previsualizacion(): void
    {
        $negocio = Negocio::factory()->create(['logo' => 'logos/existente.png']);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->assertSee('logos/existente.png', false);
    }

    public function test_cuando_no_hay_logo_se_muestra_un_icono_predeterminado(): void
    {
        $negocio = Negocio::factory()->create(['logo' => null]);
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(IdentidadNegocio::class)
            ->assertSee('Elegir logo');
    }
}
