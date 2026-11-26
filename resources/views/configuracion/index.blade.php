<x-layouts.app>
    @vite('resources/js/cropper.js')

    <div class="mb-6">
        <a href="{{ route('ventas.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
            </svg>
            Volver a ventas
        </a>
        <h1 class="mt-2 text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Configuración</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
            Identidad del negocio, usuarios y preferencias de venta.
            @if (auth()->user()->esSuperadmin())
                También puedes administrar las imágenes predeterminadas.
            @endif
        </p>
    </div>

    @if (auth()->user()->negocio === null)
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin negocio asignado</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                Tu usuario no tiene un negocio asignado, por lo que no hay configuración disponible.
            </p>
        </div>
    @else
        @php
            $seccionesConfiguracion = [
                ['id' => 'config-seccion-identidad', 'label' => 'Identidad visual del negocio', 'keywords' => 'identidad logo eslogan qr negocio imagen visual'],
                ['id' => 'config-seccion-usuarios', 'label' => 'Usuarios', 'keywords' => 'usuarios admin cajeros cuentas acceso roles'],
            ];

            if (auth()->user()->esSuperadmin()) {
                $seccionesConfiguracion[] = [
                    'id' => 'config-seccion-imagenes',
                    'label' => 'Imágenes predeterminadas',
                    'keywords' => 'imagenes predeterminadas recorte foto imagenes por defecto',
                ];
            }
        @endphp

        <div x-data="{
                busquedaSeccion: '',
                drawerAbierto: false,
                seccionActiva: '',
                indiceResaltado: 0,
                secciones: {{ Illuminate\Support\Js::from($seccionesConfiguracion) }},
                normalizar(texto) {
                    return (texto || '')
                        .toString()
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/\p{Diacritic}/gu, '');
                },
                get resultadosSecciones() {
                    const q = this.normalizar(this.busquedaSeccion).trim();
                    if (! q) return this.secciones;
                    return this.secciones.filter(s =>
                        this.normalizar(s.label).includes(q) || this.normalizar(s.keywords).includes(q)
                    );
                },
                get etiquetaSeccionActiva() {
                    return this.secciones.find(s => s.id === this.seccionActiva)?.label || '';
                },
                irASeccion(id) {
                    this.busquedaSeccion = '';
                    this.drawerAbierto = false;
                    this.seccionActiva = id;
                    this.indiceResaltado = 0;
                    window.setTimeout(() => {
                        const contenedor = document.querySelector('[data-scroll-principal]');
                        if (contenedor) contenedor.scrollTop = 0;
                    }, 50);
                },
                elegirResultadoResaltado() {
                    if (! this.busquedaSeccion.trim() || this.resultadosSecciones.length === 0) return;
                    const indice = Math.min(Math.max(this.indiceResaltado, 0), this.resultadosSecciones.length - 1);
                    this.irASeccion(this.resultadosSecciones[indice].id);
                }
            }" class="space-y-6">
            <div class="sticky top-0 z-20 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800"
                 x-on:click.outside="busquedaSeccion = ''"
                 x-on:keydown.escape.window="busquedaSeccion = ''">
                <label for="busqueda-seccion-config" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Buscar sección</label>
                <div class="relative mt-2">
                    <input id="busqueda-seccion-config"
                           type="text"
                           x-model="busquedaSeccion"
                           x-on:input="indiceResaltado = 0"
                           x-on:keydown.arrow-down.prevent="indiceResaltado = Math.min(indiceResaltado + 1, resultadosSecciones.length - 1)"
                           x-on:keydown.arrow-up.prevent="indiceResaltado = Math.max(indiceResaltado - 1, 0)"
                           x-on:keydown.enter.prevent="elegirResultadoResaltado()"
                           placeholder="Ej. imágenes, usuarios, promociones..."
                           autocomplete="off"
                           role="combobox"
                           aria-controls="resultados-secciones-config"
                           aria-autocomplete="list"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100" />
                    <div id="resultados-secciones-config"
                         x-show="busquedaSeccion.trim().length > 0"
                         x-cloak
                         role="listbox"
                         class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border border-stone-200 bg-white shadow-lg dark:border-stone-700 dark:bg-stone-800">
                        <template x-if="resultadosSecciones.length === 0">
                            <p class="px-3 py-2 text-sm text-stone-500 dark:text-stone-400">No se encontraron secciones.</p>
                        </template>
                        <template x-for="(seccion, index) in resultadosSecciones" :key="seccion.id">
                            <button type="button"
                                    role="option"
                                    x-bind:aria-selected="indiceResaltado === index ? 'true' : 'false'"
                                    x-on:click="irASeccion(seccion.id)"
                                    x-on:mouseenter="indiceResaltado = index"
                                    x-bind:class="indiceResaltado === index ? 'bg-amber-50 text-amber-900 dark:bg-amber-900/30 dark:text-amber-200' : ''"
                                    class="flex w-full items-center px-3 py-2 text-left text-sm text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700"
                                    x-text="seccion.label"></button>
                        </template>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <template x-if="seccionActiva">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-900 dark:bg-amber-900/40 dark:text-amber-200"
                                  x-text="etiquetaSeccionActiva"></span>
                            <button type="button"
                                    x-on:click="seccionActiva = ''"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-stone-300 px-3 py-1 text-xs font-semibold text-stone-700 transition hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
                                </svg>
                                Ver todas las secciones
                            </button>
                        </div>
                    </template>
                    <template x-if="! seccionActiva">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($seccionesConfiguracion as $seccion)
                                <button type="button"
                                        @click="irASeccion('{{ $seccion['id'] }}')"
                                        class="inline-flex items-center rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-semibold text-stone-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-800 dark:border-stone-600 dark:bg-stone-700/40 dark:text-stone-200 dark:hover:border-amber-600/70 dark:hover:bg-amber-900/20 dark:hover:text-amber-300">
                                    {{ $seccion['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </template>
                </div>
            </div>

            <button type="button"
                    x-on:click="drawerAbierto = true"
                    x-bind:aria-expanded="drawerAbierto ? 'true' : 'false'"
                    aria-label="Abrir índice de secciones"
                    class="fixed bottom-4 right-4 z-30 inline-flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2.5 text-sm font-bold text-stone-900 shadow-lg transition hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 lg:hidden">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Secciones
            </button>

            <div x-show="drawerAbierto"
                 x-cloak
                 x-on:keydown.escape.window="drawerAbierto = false"
                 role="dialog"
                 aria-modal="true"
                 aria-label="Secciones de configuración"
                 class="fixed inset-0 z-40">
                <div class="absolute inset-0 bg-stone-900/40 backdrop-blur-[1px]"
                     x-on:click="drawerAbierto = false"></div>
                <aside class="absolute inset-y-0 right-0 flex w-[min(20rem,85vw)] flex-col bg-white p-4 shadow-2xl dark:bg-stone-800">
                    <div class="flex items-center justify-between gap-3 border-b border-stone-200 pb-3 dark:border-stone-700">
                        <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Secciones</h2>
                        <button type="button"
                                x-on:click="drawerAbierto = false"
                                aria-label="Cerrar índice de secciones"
                                class="rounded-lg p-1.5 text-stone-500 hover:bg-stone-100 dark:text-stone-400 dark:hover:bg-stone-700">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3 flex-1 space-y-1 overflow-y-auto">
                        <template x-for="seccion in secciones" :key="seccion.id">
                            <button type="button"
                                    x-on:click="irASeccion(seccion.id)"
                                    class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700">
                                <span x-text="seccion.label"></span>
                                <svg class="size-4 shrink-0 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m9 18 6-6-6-6" />
                                </svg>
                            </button>
                        </template>
                    </div>
                    <div class="mt-3 border-t border-stone-200 pt-3 dark:border-stone-700">
                        <p class="px-3 pb-1 text-xs text-stone-500 dark:text-stone-400">Elige una sección para verla.</p>
                        <a href="{{ route('configuracion.aceptaciones.index') }}"
                           data-cargando="Registro de aceptación de términos"
                           class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/20">
                            Ver registro de aceptación de términos
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m9 18 6-6-6-6" />
                            </svg>
                        </a>
                    </div>
                </aside>
            </div>

            <div class="space-y-8">
                <section id="config-seccion-identidad"
                         x-show="! seccionActiva || seccionActiva === 'config-seccion-identidad'"
                         x-cloak>
                    <livewire:configuracion.identidad-negocio />
                </section>
                <section id="config-seccion-usuarios"
                         x-show="! seccionActiva || seccionActiva === 'config-seccion-usuarios'"
                         x-cloak>
                    <livewire:configuracion.usuarios-admin lazy />
                </section>
                @if (auth()->user()->esSuperadmin())
                    <section id="config-seccion-imagenes"
                             x-show="! seccionActiva || seccionActiva === 'config-seccion-imagenes'"
                             x-cloak>
                        <livewire:configuracion.imagenes-predeterminadas-admin lazy />
                    </section>
                @endif
            </div>
        </div>
    @endif
</x-layouts.app>
