<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CalendarioRangoTest extends TestCase
{
    public function test_muestra_un_solo_calendario_para_seleccionar_el_rango(): void
    {
        $html = Blade::render(
            '<x-calendario-rango id="prueba" etiqueta="Período" desde="2026-11-01" hasta="2026-11-20" />'
        );

        $this->assertSame(1, substr_count($html, 'role="dialog"'));
        $this->assertStringNotContainsString('type="date"', $html);
        $this->assertStringContainsString('id="prueba-boton"', $html);
        $this->assertStringContainsString('name="desde"', $html);
        $this->assertStringContainsString('value="2026-11-01"', $html);
        $this->assertStringContainsString('name="hasta"', $html);
        $this->assertStringContainsString('value="2026-11-20"', $html);
        $this->assertStringContainsString('Selecciona la fecha inicial y luego la final.', $html);
        $this->assertStringContainsString('x-for="dia in dias"', $html);
        $this->assertStringContainsString('x-on:click="seleccionar(dia.iso)"', $html);
        $this->assertStringContainsString('x-on:click="mesAnterior()"', $html);
        $this->assertStringContainsString('x-on:click="mesSiguiente()"', $html);
    }

    public function test_ignora_fechas_invalidas_en_los_parametros(): void
    {
        $html = Blade::render(
            '<x-calendario-rango id="prueba" desde="foo" hasta="2026-13-99" />'
        );

        $this->assertStringContainsString('desde: \'\'', $html);
        $this->assertStringContainsString('hasta: \'\'', $html);
        $this->assertStringContainsString('value=""', $html);
        $this->assertStringContainsString('Seleccionar rango', $html);
    }

    public function test_puede_conectar_los_campos_con_modelos_de_livewire(): void
    {
        $html = Blade::render(
            '<x-calendario-rango
                id="promocion"
                etiqueta="Vigencia"
                nombre-desde="fechaInicio"
                nombre-hasta="fechaFin"
                desde="2026-11-01"
                hasta="2026-11-20"
                :auto-enviar="false"
                wire-model-desde="fechaInicio"
                wire-model-hasta="fechaFin"
            />'
        );

        $this->assertStringContainsString('wire:model="fechaInicio"', $html);
        $this->assertStringContainsString('wire:model="fechaFin"', $html);
        $this->assertStringNotContainsString('x-model="desde"', $html);
        $this->assertStringNotContainsString('x-model="hasta"', $html);
        $this->assertStringContainsString("modeloDesde: 'fechaInicio'", $html);
        $this->assertStringContainsString("modeloHasta: 'fechaFin'", $html);
        $this->assertStringContainsString('autoEnviar: false', $html);
    }
}
