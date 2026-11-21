<?php

namespace Tests\Feature;

use App\Livewire\Configuracion\PromocionesAdmin;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Promocion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PromocionesAdminTest extends TestCase
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

    private function productoDe(Negocio $negocio, string $nombre, float $precio): Producto
    {
        $categoria = Categoria::factory()->create(['negocio_id' => $negocio->id]);

        return Producto::factory()->create([
            'categoria_id' => $categoria->id,
            'nombre' => $nombre,
            'precio' => $precio,
        ]);
    }

    /**
     * @param  array<int, array{0: Producto, 1: int}>  $items
     * @param  array<string, mixed>  $atributos
     */
    private function promocionDe(Negocio $negocio, array $items, array $atributos = []): Promocion
    {
        $promocion = Promocion::factory()->create(array_merge([
            'negocio_id' => $negocio->id,
            'nombre' => 'Combo de prueba',
            'precio' => 15,
            'fecha_inicio' => now()->startOfDay()->toDateString(),
            'fecha_fin' => now()->addWeek()->toDateString(),
            'dias_semana' => [1, 2, 3, 4, 5, 6, 7],
            'activa' => true,
        ], $atributos));

        $promocion->productos()->attach(
            collect($items)->mapWithKeys(fn (array $item): array => [
                $item[0]->id => ['cantidad' => $item[1]],
            ])->all()
        );

        return $promocion;
    }

    public function test_un_admin_puede_crear_una_promocion_con_items_y_dias(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-22 12:00:00'));

        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $flan = $this->productoDe($negocio, 'Flan', 5);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->set('nombre', 'Combo desayuno')
            ->set('descripcion', 'Café con flan')
            ->set('precio', '18.00')
            ->set('fechaInicio', '2026-10-22')
            ->set('fechaFin', '2026-10-31')
            ->set('diasSemana', [1, 2, 3, 4, 5])
            ->set('items', [
                ['producto_id' => $cafe->id, 'cantidad' => 1],
                ['producto_id' => $flan->id, 'cantidad' => 2],
            ])
            ->set('activa', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('nombre', '')
            ->assertSet('editandoId', null)
            ->assertDispatched('toast', message: 'Guardado exitosamente');

        $promocion = Promocion::query()->firstOrFail();

        $this->assertSame($negocio->id, $promocion->negocio_id);
        $this->assertSame('Combo desayuno', $promocion->nombre);
        $this->assertSame('Café con flan', $promocion->descripcion);
        $this->assertSame('18.00', $promocion->precio);
        $this->assertSame('2026-10-22', $promocion->fecha_inicio->format('Y-m-d'));
        $this->assertSame('2026-10-31', $promocion->fecha_fin->format('Y-m-d'));
        $this->assertSame([1, 2, 3, 4, 5], $promocion->dias_semana);
        $this->assertTrue($promocion->activa);
        $this->assertCount(2, $promocion->productos);
        $this->assertSame(1, (int) $promocion->productos()->whereKey($cafe->id)->firstOrFail()->pivot->cantidad);
        $this->assertSame(2, (int) $promocion->productos()->whereKey($flan->id)->firstOrFail()->pivot->cantidad);
    }

    public function test_el_precio_promocional_no_puede_superar_el_total_normal(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->set('nombre', 'Combo caro')
            ->set('precio', '10.01')
            ->set('fechaInicio', now()->toDateString())
            ->set('fechaFin', now()->addWeek()->toDateString())
            ->set('diasSemana', [1])
            ->set('items', [['producto_id' => $cafe->id, 'cantidad' => 1]])
            ->call('guardar')
            ->assertHasErrors('precio');

        $this->assertDatabaseCount('promociones', 0);
    }

    public function test_un_admin_puede_editar_una_promocion(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $flan = $this->productoDe($negocio, 'Flan', 5);
        $promocion = $this->promocionDe($negocio, [[$cafe, 1], [$flan, 1]]);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->call('editar', $promocion->id)
            ->assertSet('editandoId', $promocion->id)
            ->assertSet('nombre', 'Combo de prueba')
            ->set('nombre', 'Combo actualizado')
            ->set('precio', '12.00')
            ->set('items', [
                ['producto_id' => $cafe->id, 'cantidad' => 2],
                ['producto_id' => $flan->id, 'cantidad' => 1],
            ])
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSet('editandoId', null);

        $promocion->refresh();

        $this->assertSame('Combo actualizado', $promocion->nombre);
        $this->assertSame('12.00', $promocion->precio);
        $this->assertCount(2, $promocion->productos);
        $this->assertSame(2, (int) $promocion->productos()->whereKey($cafe->id)->firstOrFail()->pivot->cantidad);
    }

    public function test_un_admin_puede_eliminar_una_promocion_con_confirmacion(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $promocion = $this->promocionDe($negocio, [[$cafe, 1]]);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->call('confirmarEliminacion', $promocion->id)
            ->assertSet('eliminandoId', $promocion->id)
            ->call('eliminar')
            ->assertSet('eliminandoId', null);

        $this->assertModelMissing($promocion);
    }

    public function test_un_cajero_no_puede_crear_promociones(): void
    {
        $negocio = Negocio::factory()->create();
        $cajero = $this->cajeroDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);

        Livewire::actingAs($cajero)
            ->test(PromocionesAdmin::class)
            ->set('nombre', 'Combo no autorizado')
            ->set('precio', '8.00')
            ->set('fechaInicio', now()->toDateString())
            ->set('fechaFin', now()->addWeek()->toDateString())
            ->set('diasSemana', [1])
            ->set('items', [['producto_id' => $cafe->id, 'cantidad' => 1]])
            ->call('guardar')
            ->assertForbidden();

        $this->assertDatabaseCount('promociones', 0);
    }

    public function test_un_admin_no_puede_editar_una_promocion_de_otro_negocio(): void
    {
        $negocio = Negocio::factory()->create();
        $otroNegocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafeAjeno = $this->productoDe($otroNegocio, 'Café ajeno', 10);
        $promocionAjena = $this->promocionDe($otroNegocio, [[$cafeAjeno, 1]]);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->call('editar', $promocionAjena->id)
            ->assertNotFound();
    }

    public function test_la_creacion_y_edicion_de_promociones_se_abren_en_un_modal(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);
        $cafe = $this->productoDe($negocio, 'Café', 10);
        $promocion = $this->promocionDe($negocio, [[$cafe, 1]], ['nombre' => 'Combo de prueba']);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->assertSet('modalAbierto', false)
            ->assertDontSeeHtml('wire:keydown.escape.window="cerrarModal"')
            ->call('abrirModal')
            ->assertSet('modalAbierto', true)
            ->assertSeeHtml('wire:keydown.escape.window="cerrarModal"')
            ->call('cerrarModal')
            ->assertSet('modalAbierto', false)
            ->assertDontSeeHtml('wire:keydown.escape.window="cerrarModal"')
            ->call('editar', $promocion->id)
            ->assertSet('modalAbierto', true)
            ->assertSeeHtml('wire:keydown.escape.window="cerrarModal"')
            ->assertSet('editandoId', $promocion->id)
            ->assertSet('nombre', 'Combo de prueba');
    }

    public function test_el_listado_de_promociones_se_puede_buscar_y_pagina_de_diez(): void
    {
        $negocio = Negocio::factory()->create();
        $admin = $this->adminDe($negocio);

        foreach (range(1, 12) as $numero) {
            Promocion::factory()->create([
                'negocio_id' => $negocio->id,
                'nombre' => 'Promoción '.str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
            ]);
        }

        $otroNegocio = Negocio::factory()->create();
        Promocion::factory()->create([
            'negocio_id' => $otroNegocio->id,
            'nombre' => 'Promoción Ajena',
        ]);

        Livewire::actingAs($admin)
            ->test(PromocionesAdmin::class)
            ->assertSee('Página 1 de 2')
            ->assertSee('12 promociones')
            ->assertDontSee('Promoción Ajena')
            ->set('buscar', 'Promoción 07')
            ->assertSee('Promoción 07')
            ->assertDontSee('Promoción 01')
            ->assertDontSee('Página 1 de 2');
    }
}
