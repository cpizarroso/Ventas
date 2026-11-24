@props([
    'desde' => null,
    'hasta' => null,
    'id' => 'calendario-rango',
    'etiqueta' => 'Rango de fechas',
    'nombreDesde' => 'desde',
    'nombreHasta' => 'hasta',
    'autoEnviar' => true,
    'wireModelDesde' => null,
    'wireModelHasta' => null,
])

@php
    $desdeTexto = $desde instanceof DateTimeInterface ? $desde->format('Y-m-d') : (string) $desde;
    $hastaTexto = $hasta instanceof DateTimeInterface ? $hasta->format('Y-m-d') : (string) $hasta;
    $desdeValido = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $desdeTexto, $partesDesde) === 1
        && checkdate((int) $partesDesde[2], (int) $partesDesde[3], (int) $partesDesde[1]);
    $hastaValido = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $hastaTexto, $partesHasta) === 1
        && checkdate((int) $partesHasta[2], (int) $partesHasta[3], (int) $partesHasta[1]);
    $desdeTexto = $desdeValido ? $desdeTexto : '';
    $hastaTexto = $hastaValido ? $hastaTexto : '';
    $fechaBase = $desdeTexto ?: ($hastaTexto ?: now()->toDateString());
    $partesBase = explode('-', $fechaBase);
    $anioInicial = (int) ($partesBase[0] ?? now()->year);
    $mesInicial = max(1, min(12, (int) ($partesBase[1] ?? now()->month))) - 1;
    $diasSemana = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
    $meses = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'relative w-full sm:w-80']) }}
    x-data="{
        desde: @js($desdeTexto),
        hasta: @js($hastaTexto),
        anio: {{ $anioInicial }},
        mes: {{ $mesInicial }},
        abierto: false,
        hover: '',
        meses: @js($meses),
        diasSemana: @js($diasSemana),
        modeloDesde: @js($wireModelDesde),
        modeloHasta: @js($wireModelHasta),
        autoEnviar: @js((bool) $autoEnviar),

        get etiqueta() {
            if (! this.desde) {
                return 'Seleccionar rango';
            }

            if (! this.hasta) {
                return this.formatear(this.desde) + ' — …';
            }

            return this.formatear(this.desde) + ' — ' + this.formatear(this.hasta);
        },

        get tituloMes() {
            return this.meses[this.mes] + ' ' + this.anio;
        },

        get dias() {
            const primerDia = new Date(this.anio, this.mes, 1);
            const desplazamiento = (primerDia.getDay() + 6) % 7;
            const cantidadDias = new Date(this.anio, this.mes + 1, 0).getDate();
            const totalCasillas = Math.ceil((desplazamiento + cantidadDias) / 7) * 7;
            const resultado = [];

            for (let indice = 0; indice < totalCasillas; indice++) {
                const fecha = new Date(this.anio, this.mes, indice - desplazamiento + 1);
                resultado.push({
                    iso: this.iso(fecha.getFullYear(), fecha.getMonth(), fecha.getDate()),
                    dia: fecha.getDate(),
                    otroMes: fecha.getMonth() !== this.mes,
                });
            }

            return resultado;
        },

        iso(anio, mes, dia) {
            return anio + '-' + String(mes + 1).padStart(2, '0') + '-' + String(dia).padStart(2, '0');
        },

        formatear(iso) {
            if (! iso) {
                return '—';
            }

            const partes = iso.split('-');

            return partes[2] + '/' + partes[1] + '/' + partes[0];
        },

        mesAnterior() {
            const fecha = new Date(this.anio, this.mes - 1, 1);
            this.anio = fecha.getFullYear();
            this.mes = fecha.getMonth();
        },

        mesSiguiente() {
            const fecha = new Date(this.anio, this.mes + 1, 1);
            this.anio = fecha.getFullYear();
            this.mes = fecha.getMonth();
        },

        esInicio(iso) {
            return iso === this.desde;
        },

        esFin(iso) {
            return Boolean(this.hasta) && iso === this.hasta;
        },

        enRango(iso) {
            const fin = this.hasta || (this.desde && this.hover >= this.desde ? this.hover : '');

            return Boolean(this.desde && fin && iso >= this.desde && iso <= fin);
        },

        seleccionar(iso) {
            if (! this.desde || this.hasta) {
                this.desde = iso;
                this.hasta = '';
                this.hover = '';
                this.sincronizar();

                return;
            }

            if (iso < this.desde) {
                this.hasta = this.desde;
                this.desde = iso;
            } else {
                this.hasta = iso;
            }

            this.hover = '';
            this.sincronizar();
            this.abierto = false;

            if (this.autoEnviar) {
                this.$nextTick(() => {
                    const formulario = this.$el.closest('form');

                    if (formulario) {
                        formulario.requestSubmit();
                    }
                });
            }
        },

        sincronizar() {
            if (typeof this.$wire === 'undefined' || ! this.$wire) {
                return;
            }

            if (this.modeloDesde) {
                this.$wire.set(this.modeloDesde, this.desde);
            }

            if (this.modeloHasta) {
                this.$wire.set(this.modeloHasta, this.hasta);
            }
        },
    }"
    x-on:click.outside="abierto = false"
    x-on:keydown.escape.window="abierto = false"
>
    <label for="{{ $id }}-boton" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">{{ $etiqueta }}</label>

    <input type="hidden" name="{{ $nombreDesde }}" value="{{ $desdeTexto }}" @if ($wireModelDesde) wire:model="{{ $wireModelDesde }}" @else x-model="desde" @endif>
    <input type="hidden" name="{{ $nombreHasta }}" value="{{ $hastaTexto }}" @if ($wireModelHasta) wire:model="{{ $wireModelHasta }}" @else x-model="hasta" @endif>

    <button
        id="{{ $id }}-boton"
        type="button"
        x-on:click.stop="abierto = ! abierto"
        x-bind:aria-expanded="abierto ? 'true' : 'false'"
        aria-haspopup="dialog"
        aria-controls="{{ $id }}-panel"
        class="flex w-full items-center gap-2 rounded-xl border border-stone-300 bg-white px-3 py-2 text-left text-sm text-stone-800 hover:border-amber-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
    >
        <svg class="size-4 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M8 3v4m8-4v4M3 10h18" />
        </svg>
        <span class="min-w-0 flex-1 truncate tabular-nums" x-text="etiqueta">{{ $desdeTexto && $hastaTexto ? $desdeTexto.' — '.$hastaTexto : 'Seleccionar rango' }}</span>
        <svg class="size-4 shrink-0 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div
        id="{{ $id }}-panel"
        role="dialog"
        aria-label="{{ $etiqueta }}"
        x-show="abierto"
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
        style="display: none"
        class="absolute left-0 z-40 mt-2 w-72 origin-top-left rounded-2xl border border-stone-200 bg-white p-3 shadow-xl dark:border-stone-700 dark:bg-stone-800"
    >
        <div class="mb-2 flex items-center justify-between gap-2">
            <button type="button" x-on:click="mesAnterior()" aria-label="Mes anterior"
                    class="rounded-lg p-1.5 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-stone-100">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </button>
            <p class="text-sm font-semibold capitalize text-stone-800 dark:text-stone-100" x-text="tituloMes">{{ $meses[$mesInicial] }} {{ $anioInicial }}</p>
            <button type="button" x-on:click="mesSiguiente()" aria-label="Mes siguiente"
                    class="rounded-lg p-1.5 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-stone-100">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase text-stone-400 dark:text-stone-500">
            <template x-for="diaSemana in diasSemana" :key="diaSemana">
                <span x-text="diaSemana"></span>
            </template>
        </div>

        <div class="mt-1 grid grid-cols-7 gap-1">
            <template x-for="dia in dias" :key="dia.iso">
                <button
                    type="button"
                    x-on:click="seleccionar(dia.iso)"
                    x-on:mouseenter="hover = dia.iso"
                    x-bind:aria-label="formatear(dia.iso)"
                    x-bind:aria-pressed="esInicio(dia.iso) || esFin(dia.iso) ? 'true' : 'false'"
                    x-bind:class="{
                        'bg-amber-500 font-bold text-white': esInicio(dia.iso) || esFin(dia.iso),
                        'bg-amber-100 text-amber-900 dark:bg-amber-900/50 dark:text-amber-100': enRango(dia.iso) && ! esInicio(dia.iso) && ! esFin(dia.iso),
                        'text-stone-400 dark:text-stone-600': dia.otroMes,
                    }"
                    x-text="dia.dia"
                    class="flex size-8 items-center justify-center rounded-lg text-xs text-stone-700 hover:bg-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-200 dark:hover:bg-stone-700"
                ></button>
            </template>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-2 border-t border-stone-100 pt-2 text-xs dark:border-stone-700">
            <p class="text-stone-500 dark:text-stone-400">Inicio <span class="block font-semibold text-stone-800 tabular-nums dark:text-stone-100" x-text="formatear(desde)"></span></p>
            <p class="text-stone-500 dark:text-stone-400">Fin <span class="block font-semibold text-stone-800 tabular-nums dark:text-stone-100" x-text="formatear(hasta)"></span></p>
        </div>
        <p class="mt-2 text-[10px] text-stone-400 dark:text-stone-500">Selecciona la fecha inicial y luego la final.</p>
    </div>
</div>
