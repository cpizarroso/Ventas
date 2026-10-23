<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Livewire\Configuracion\CategoriasAdmin;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategoriasAdminTest extends TestCase
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

    public function test_un_negocio_nuevo_comienza_sin_categorias(): void
    {
        $negocio = Negocio::factory()->create();

        $this->assertSame(0, $negocio->categorias()->count());
    }

    public function test_un_admin_puede_crear_una_categoria_para_su_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertHasNoErrors()
            ->assertSet('nombre', '');

        $this->assertDatabaseHas('categorias', [
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
            'activa' => true,
        ]);
    }

    public function test_un_admin_puede_modificar_el_nombre_de_una_categoria(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicion', $categoria->id)
            ->set('nombreEdicion', 'Refrescos')
            ->call('guardarEdicion')
            ->assertHasNoErrors()
            ->assertSet('editandoId', null);

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nombre' => 'Refrescos',
        ]);
    }

    public function test_un_admin_puede_eliminar_una_categoria_de_su_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoria->id)
            ->assertSet('eliminandoId', $categoria->id)
            ->call('eliminar')
            ->assertSet('eliminandoId', null);

        $this->assertModelMissing($categoria);
    }

    public function test_los_cambios_de_las_categorias_persisten_en_la_base_de_datos(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Postres')
            ->call('crear');

        $categoria = $negocio->categorias()->where('nombre', 'Postres')->firstOrFail();

        $this->assertNotNull($categoria->created_at);
        $this->assertNotNull($categoria->updated_at);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicion', $categoria->id)
            ->set('nombreEdicion', 'Dulces')
            ->call('guardarEdicion');

        $this->assertTrue($categoria->fresh()->nombre === 'Dulces');
    }

    public function test_un_cajero_no_puede_crear_modificar_ni_eliminar_categorias(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertForbidden();

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicion', $categoria->id)
            ->assertForbidden();

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoria->id)
            ->assertForbidden();

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
        ]);
    }

    public function test_un_admin_no_puede_modificar_las_categorias_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaAjena = Categoria::factory()->create([
            'negocio_id' => $negocioAjeno->id,
            'nombre' => 'Bebidas',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicion', $categoriaAjena->id)
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoriaAjena->id)
            ->assertNotFound();

        $this->assertModelExists($categoriaAjena);
    }

    public function test_dos_negocios_pueden_utilizar_el_mismo_nombre_de_categoria(): void
    {
        $negocioA = Negocio::factory()->create();
        $negocioB = Negocio::factory()->create();
        $adminA = $this->adminDe($negocioA);
        $adminB = $this->adminDe($negocioB);

        Livewire::actingAs($adminA)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertHasNoErrors();

        Livewire::actingAs($adminB)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertHasNoErrors();

        $this->assertSame(1, $negocioA->categorias()->where('nombre', 'Bebidas')->count());
        $this->assertSame(1, $negocioB->categorias()->where('nombre', 'Bebidas')->count());
    }

    public function test_se_rechazan_nombres_de_categoria_vacios_o_invalidos(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', '')
            ->call('crear')
            ->assertHasErrors(['nombre' => 'required']);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'a')
            ->call('crear')
            ->assertHasErrors(['nombre' => 'min']);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', str_repeat('x', 81))
            ->call('crear')
            ->assertHasErrors(['nombre' => 'max']);

        $this->assertSame(0, $negocio->categorias()->count());
    }

    public function test_se_rechaza_un_nombre_duplicado_dentro_del_mismo_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        Categoria::factory()->create([
            'negocio_id' => $negocio->id,
            'nombre' => 'Bebidas',
        ]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertHasErrors(['nombre']);

        $this->assertSame(1, $negocio->categorias()->count());
    }

    public function test_el_superadmin_no_puede_gestionar_sin_negocio_asignado(): void
    {
        $negocio = Negocio::factory()->create();
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $superadmin = User::factory()->superadmin()->create();

        Livewire::actingAs($superadmin)
            ->test(CategoriasAdmin::class)
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertForbidden();

        Livewire::actingAs($superadmin)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoria->id)
            ->assertForbidden();

        $this->assertModelExists($categoria);
    }

    public function test_las_restricciones_se_aplican_en_el_backend_no_solo_en_la_interfaz(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);

        $this->assertFalse($cajero->can('create', Categoria::class));
        $this->assertSame(Rol::Cajero, $cajero->rol);
    }
}
