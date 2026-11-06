<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\CategoriasAdmin;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class ProductosAdminTest extends TestCase
{
    use RefreshDatabase;

    private function adminDe(Negocio $negocio): User
    {
        return User::factory()->admin()->create(['negocio_id' => $negocio->id]);
    }

    private function abrirCategoria(User $admin, Categoria $categoria): Testable
    {
        return Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('abrirProductos', $categoria->id)
            ->assertSet('productosAbiertosId', $categoria->id);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('framework/testing/productos-predeterminados'));

        parent::tearDown();
    }

    private function directorioImagenesPredeterminadas(): string
    {
        $directorio = storage_path('framework/testing/productos-predeterminados');

        File::deleteDirectory($directorio);
        File::ensureDirectoryExists($directorio);

        config(['productos.imagenes_predeterminadas' => $directorio]);

        return $directorio;
    }

    private function crearImagenPredeterminada(string $directorio, string $nombre, int $ancho = 900, int $alto = 700): void
    {
        $imagen = imagecreatetruecolor($ancho, $alto);

        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 200, 150, 100));
        imagejpeg($imagen, $directorio.DIRECTORY_SEPARATOR.$nombre, 85);
        imagedestroy($imagen);
    }

    public function test_las_categorias_creadas_aparecen_con_la_opcion_de_items(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        Categoria::factory()->create(['negocio_id' => $negocio->id, 'nombre' => 'Bebidas']);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->assertSee('Bebidas')
            ->assertSee('Ítems');
    }

    public function test_un_admin_puede_crear_un_item_con_nombre_y_precio(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café con leche')
            ->set('productoPrecio', '75.50')
            ->call('crearProducto')
            ->assertHasNoErrors()
            ->assertSet('productoNombre', '')
            ->assertSet('productoPrecio', '')
            ->assertDispatched('toast', message: 'Guardado exitosamente');

        $this->assertDatabaseHas('productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Café con leche',
            'precio' => 75.50,
        ]);
    }

    public function test_un_admin_puede_crear_un_item_con_stock_limitado(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café con leche')
            ->set('productoPrecio', '75.50')
            ->set('productoStock', '12')
            ->call('crearProducto')
            ->assertHasNoErrors()
            ->assertSet('productoStock', '');

        $this->assertDatabaseHas('productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Café con leche',
            'stock' => 12,
        ]);
    }

    public function test_un_item_sin_stock_queda_ilimitado(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café con leche')
            ->set('productoPrecio', '75.50')
            ->set('productoStock', '')
            ->call('crearProducto')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('productos', [
            'categoria_id' => $categoria->id,
            'nombre' => 'Café con leche',
            'stock' => null,
        ]);
    }

    public function test_se_rechaza_un_stock_negativo_o_con_decimales(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->set('productoStock', '-1')
            ->call('crearProducto')
            ->assertHasErrors(['productoStock' => 'min']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->set('productoStock', '1.5')
            ->call('crearProducto')
            ->assertHasErrors(['productoStock' => 'integer']);

        $this->assertSame(0, $categoria->productos()->count());
    }

    public function test_un_admin_puede_modificar_el_stock_de_un_item(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Café',
            'precio' => 50,
            'stock' => 5,
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->assertSet('productoStockEdicion', '5')
            ->set('productoStockEdicion', '20')
            ->call('guardarEdicionProducto')
            ->assertHasNoErrors()
            ->assertSet('productoStockEdicion', '')
            ->assertDispatched('toast', message: 'Guardado exitosamente');

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'stock' => 20,
        ]);
    }

    public function test_un_admin_puede_modificar_y_eliminar_un_item(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Café',
            'precio' => 50,
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->set('productoNombreEdicion', 'Café americano')
            ->set('productoPrecioEdicion', '55.25')
            ->call('guardarEdicionProducto')
            ->assertHasNoErrors()
            ->assertSet('productoEditandoId', null)
            ->assertDispatched('toast', message: 'Guardado exitosamente');

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Café americano',
            'precio' => 55.25,
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('confirmarEliminacionProducto', $producto->id)
            ->assertSet('productoEliminandoId', $producto->id)
            ->call('eliminarProducto')
            ->assertSet('productoEliminandoId', null)
            ->assertNotDispatched('toast');

        $this->assertModelMissing($producto);
    }

    public function test_se_rechazan_items_sin_nombre_o_con_precio_invalido(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', '')
            ->set('productoPrecio', '')
            ->call('crearProducto')
            ->assertHasErrors(['productoNombre' => 'required', 'productoPrecio' => 'required']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '-5')
            ->call('crearProducto')
            ->assertHasErrors(['productoPrecio' => 'min']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10.999')
            ->call('crearProducto')
            ->assertHasErrors(['productoPrecio' => 'decimal']);

        $this->assertSame(0, $categoria->productos()->count());
    }

    public function test_se_rechaza_un_item_duplicado_dentro_de_la_misma_categoria(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'nombre' => 'Café']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->call('crearProducto')
            ->assertHasErrors(['productoNombre']);

        $this->assertSame(1, $categoria->productos()->count());
    }

    public function test_un_cajero_no_puede_gestionar_items(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = User::factory()->cajero()->create(['negocio_id' => $negocio->id]);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('abrirProductos', $categoria->id)
            ->assertForbidden();

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicionProducto', $producto->id)
            ->assertForbidden();

        Livewire::actingAs($cajero)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacionProducto', $producto->id)
            ->assertForbidden();

        $this->assertModelExists($producto);
    }

    public function test_un_admin_no_puede_gestionar_items_de_otro_negocio(): void
    {
        $negocioPropio = Negocio::factory()->create();
        $negocioAjeno = Negocio::factory()->create();
        $admin = $this->adminDe($negocioPropio);
        $categoriaAjena = Categoria::factory()->create(['negocio_id' => $negocioAjeno->id]);
        $productoAjeno = Producto::factory()->create(['categoria_id' => $categoriaAjena->id]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('abrirProductos', $categoriaAjena->id)
            ->assertNotFound();

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('iniciarEdicionProducto', $productoAjeno->id)
            ->assertNotFound();

        $this->assertModelExists($productoAjeno);
    }

    public function test_al_eliminar_una_categoria_se_eliminan_sus_items(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoria->id)
            ->call('eliminar');

        $this->assertModelMissing($categoria);
        $this->assertModelMissing($producto);
    }

    public function test_un_admin_puede_crear_un_item_con_imagen_optimizada(): void
    {
        Storage::fake('public');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café con leche')
            ->set('productoPrecio', '75.50')
            ->set('productoImagen', UploadedFile::fake()->image('producto.jpg', 1200, 900))
            ->call('crearProducto')
            ->assertHasNoErrors()
            ->assertSet('productoImagen', null);

        $producto = Producto::query()->firstOrFail();

        $this->assertNotNull($producto->imagen);
        Storage::disk('public')->assertExists($producto->imagen);

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path($producto->imagen));

        $this->assertSame(800, $ancho);
        $this->assertSame(800, $alto);
    }

    public function test_se_rechaza_una_imagen_con_formato_o_peso_invalido(): void
    {
        Storage::fake('public');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->set('productoImagen', UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'))
            ->call('crearProducto')
            ->assertHasErrors(['productoImagen']);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->set('productoImagen', UploadedFile::fake()->image('grande.jpg', 800, 800)->size(3000))
            ->call('crearProducto')
            ->assertHasErrors(['productoImagen' => 'max']);

        $this->assertSame(0, $categoria->productos()->count());
    }

    public function test_reemplazar_la_imagen_elimina_la_anterior(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/antigua.jpg', 'contenido');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'imagen' => 'productos/antigua.jpg',
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->set('productoImagenEdicion', UploadedFile::fake()->image('nueva.png', 500, 400))
            ->call('guardarEdicionProducto')
            ->assertHasNoErrors();

        $producto->refresh();

        Storage::disk('public')->assertMissing('productos/antigua.jpg');
        Storage::disk('public')->assertExists($producto->imagen);
        $this->assertNotSame('productos/antigua.jpg', $producto->imagen);
    }

    public function test_quitar_la_imagen_en_edicion_elimina_el_archivo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/actual.jpg', 'contenido');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'imagen' => 'productos/actual.jpg',
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->set('quitarImagenEdicion', true)
            ->call('guardarEdicionProducto')
            ->assertHasNoErrors();

        $this->assertNull($producto->refresh()->imagen);
        Storage::disk('public')->assertMissing('productos/actual.jpg');
    }

    public function test_al_eliminar_un_item_se_elimina_su_imagen(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/item.jpg', 'contenido');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'imagen' => 'productos/item.jpg',
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('confirmarEliminacionProducto', $producto->id)
            ->call('eliminarProducto');

        $this->assertModelMissing($producto);
        Storage::disk('public')->assertMissing('productos/item.jpg');
    }

    public function test_al_eliminar_una_categoria_se_eliminan_las_imagenes_de_sus_items(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/uno.jpg', 'contenido');
        Storage::disk('public')->put('productos/dos.jpg', 'contenido');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'imagen' => 'productos/uno.jpg']);
        Producto::factory()->create(['categoria_id' => $categoria->id, 'imagen' => 'productos/dos.jpg']);

        Livewire::actingAs($admin)
            ->test(CategoriasAdmin::class)
            ->call('confirmarEliminacion', $categoria->id)
            ->call('eliminar');

        Storage::disk('public')->assertMissing('productos/uno.jpg');
        Storage::disk('public')->assertMissing('productos/dos.jpg');
    }

    public function test_el_modal_de_imagenes_predeterminadas_solo_esta_disponible_en_los_items(): void
    {
        $directorio = $this->directorioImagenesPredeterminadas();
        $this->crearImagenPredeterminada($directorio, 'cafe.jpg');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $componente = Livewire::actingAs($admin)->test(CategoriasAdmin::class);

        $componente
            ->assertSet('mostrarGaleriaPredeterminada', false)
            ->assertDontSee('Imágenes predeterminadas')
            ->assertDontSee('cafe.jpg')
            ->call('abrirProductos', $categoria->id)
            ->assertSee('Imágenes predeterminadas')
            ->assertDontSee('cafe.jpg')
            ->call('abrirGaleriaPredeterminada')
            ->assertSet('mostrarGaleriaPredeterminada', true)
            ->assertSee('Elegir imagen predeterminada')
            ->assertSee('cafe.jpg')
            ->call('cerrarGaleriaPredeterminada')
            ->assertSet('mostrarGaleriaPredeterminada', false)
            ->assertDontSee('cafe.jpg');
    }

    public function test_el_modal_de_imagenes_predeterminadas_funciona_en_la_edicion(): void
    {
        $directorio = $this->directorioImagenesPredeterminadas();
        $this->crearImagenPredeterminada($directorio, 'cafe.jpg');

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create(['categoria_id' => $categoria->id]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->call('abrirGaleriaPredeterminadaEdicion')
            ->assertSet('mostrarGaleriaPredeterminadaEdicion', true)
            ->assertSee('Elegir imagen predeterminada')
            ->assertSee('cafe.jpg')
            ->call('seleccionarImagenPredeterminadaEdicion', 0)
            ->assertSet('mostrarGaleriaPredeterminadaEdicion', false)
            ->assertSet('productoImagenPredeterminadaEdicion', 'cafe.jpg');
    }

    public function test_un_admin_puede_crear_un_item_con_una_imagen_predeterminada(): void
    {
        Storage::fake('public');

        $directorio = $this->directorioImagenesPredeterminadas();
        $this->crearImagenPredeterminada($directorio, 'cafe.jpg', 1200, 900);

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café con leche')
            ->set('productoPrecio', '75.50')
            ->call('abrirGaleriaPredeterminada')
            ->assertSet('mostrarGaleriaPredeterminada', true)
            ->call('seleccionarImagenPredeterminada', 0)
            ->assertSet('mostrarGaleriaPredeterminada', false)
            ->assertSet('productoImagenPredeterminada', 'cafe.jpg')
            ->call('crearProducto')
            ->assertHasNoErrors()
            ->assertSet('productoImagenPredeterminada', null);

        $producto = Producto::query()->firstOrFail();

        $this->assertNotNull($producto->imagen);
        Storage::disk('public')->assertExists($producto->imagen);

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path($producto->imagen));

        $this->assertSame(800, $ancho);
        $this->assertSame(800, $alto);
    }

    public function test_una_imagen_predeterminada_pequena_conserva_su_tamano(): void
    {
        Storage::fake('public');

        $directorio = $this->directorioImagenesPredeterminadas();
        $this->crearImagenPredeterminada($directorio, 'pequena.jpg', 256, 256);

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Icono pequeño')
            ->set('productoPrecio', '5')
            ->call('seleccionarImagenPredeterminada', 0)
            ->call('crearProducto')
            ->assertHasNoErrors();

        $producto = Producto::query()->firstOrFail();

        [$ancho, $alto] = getimagesize(Storage::disk('public')->path($producto->imagen));

        $this->assertSame(256, $ancho);
        $this->assertSame(256, $alto);
    }

    public function test_se_rechaza_una_imagen_predeterminada_inexistente(): void
    {
        Storage::fake('public');

        $this->directorioImagenesPredeterminadas();

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        $this->abrirCategoria($admin, $categoria)
            ->set('productoNombre', 'Café')
            ->set('productoPrecio', '10')
            ->set('productoImagenPredeterminada', 'inexistente.jpg')
            ->call('crearProducto')
            ->assertHasErrors(['productoImagenPredeterminada' => 'in']);

        $this->assertSame(0, $categoria->productos()->count());
    }

    public function test_reemplazar_la_imagen_con_una_predeterminada_elimina_la_anterior(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('productos/antigua.jpg', 'contenido');

        $directorio = $this->directorioImagenesPredeterminadas();
        $this->crearImagenPredeterminada($directorio, 'cafe.jpg', 1000, 800);

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);
        $producto = Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'imagen' => 'productos/antigua.jpg',
        ]);

        $this->abrirCategoria($admin, $categoria)
            ->call('iniciarEdicionProducto', $producto->id)
            ->call('seleccionarImagenPredeterminadaEdicion', 0)
            ->assertSet('productoImagenPredeterminadaEdicion', 'cafe.jpg')
            ->call('guardarEdicionProducto')
            ->assertHasNoErrors();

        $producto->refresh();

        Storage::disk('public')->assertMissing('productos/antigua.jpg');
        Storage::disk('public')->assertExists($producto->imagen);
        $this->assertNotSame('productos/antigua.jpg', $producto->imagen);
    }
}
