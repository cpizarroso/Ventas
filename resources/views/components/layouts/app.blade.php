<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <title>{{ $title ?? __('Ventas') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script>
        // Aplicar el tema guardado antes del primer pintado para evitar destellos.
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="h-dvh overflow-hidden font-sans">
    <div class="flex h-full min-h-0 flex-col">
        <header
            x-data="{ mobileMenuOpen: false, notificacionesAbiertas: false }"
            x-on:keydown.escape.window="mobileMenuOpen = false; notificacionesAbiertas = false"
            x-on:click.outside="mobileMenuOpen = false; notificacionesAbiertas = false"
            x-on:resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false"
            x-on:abrir-notificaciones-caja.window="notificacionesAbiertas = true"
            class="z-50 shrink-0 border-b border-stone-200 bg-white dark:border-stone-700 dark:bg-stone-800"
        >
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    @auth
                        <a href="{{ route('ventas.index') }}"
                           data-cargando="Ventas"
                           class="flex min-w-0 items-center gap-3 rounded-lg -m-1 p-1 hover:bg-stone-100 dark:hover:bg-stone-700/60"
                           aria-label="Ir a la pantalla principal de ventas">
                            @if ($negocioNav ?? null)
                                @if ($logoNavUrl = $negocioNav->logoOptimizadoUrl())
                                    <img src="{{ $logoNavUrl }}"
                                         alt="Logo de {{ $negocioNav->nombre_comercial }}"
                                         class="size-10 shrink-0 rounded-xl object-cover ring-1 ring-stone-200 dark:ring-stone-600">
                                @else
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" aria-hidden="true">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 9l1.5-5h15L21 9" />
                                            <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                                            <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                                            <path d="M9 20v-5h6v5" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">
                                        {{ $negocioNav->nombre_comercial }}
                                    </p>
                                    @if ($negocioNav->eslogan)
                                        <p class="truncate text-xs text-stone-500 dark:text-stone-400">
                                            {{ $negocioNav->eslogan }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <div class="flex min-w-0 items-center gap-2">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" aria-hidden="true">
                                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 9l1.5-5h15L21 9" />
                                            <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                                        </svg>
                                    </div>
                                    <span class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ config('app.name') }}</span>
                                </div>
                            @endif
                        </a>
                    @else
                        <span class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ config('app.name') }}</span>
                    @endauth
                </div>

                <div class="flex items-center justify-end gap-1.5 sm:gap-2">
                    @auth
                        <div class="hidden items-center gap-1.5 lg:flex sm:gap-2">
                            <x-nav-link :href="route('ventas.index')" :active="request()->routeIs('ventas.*')" data-cargando="Ventas">
                                Ventas
                            </x-nav-link>

                            <x-nav-link :href="route('cajas.index')" :active="request()->routeIs('cajas.*')" data-cargando="Cajas">
                                Cajas
                            </x-nav-link>

                            @if (auth()->user()->puedeGestionarConfiguracion())
                                <x-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*')" data-cargando="Productos">
                                    Productos
                                </x-nav-link>
                            @endif

                            @can('ver-reportes')
                                <x-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.*')" data-cargando="Reportes">
                                    Reportes
                                </x-nav-link>
                            @endcan

                            @if (auth()->user()->puedeGestionarConfiguracion())
                                <x-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*')" data-cargando="Configuración">
                                    Configuración
                                </x-nav-link>
                            @endif
                        </div>

                        <div class="relative">
                            <button
                                type="button"
                                x-on:click="notificacionesAbiertas = ! notificacionesAbiertas"
                                x-bind:aria-expanded="notificacionesAbiertas ? 'true' : 'false'"
                                aria-label="{{ $cajaNav !== null ? 'Caja abierta. Ver notificaciones' : 'Sin caja abierta. Ver notificaciones' }}"
                                title="Estado de la caja"
                                class="relative rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
                            >
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="6" width="18" height="13" rx="2" />
                                    <path d="M8 6V4h8v2M3 12h18M9 12v3" />
                                </svg>
                                <span @class([
                                    'absolute right-1.5 top-1.5 size-2 rounded-full ring-2 ring-white dark:ring-stone-800',
                                    'bg-emerald-500' => $cajaNav !== null,
                                    'bg-amber-400' => $cajaNav === null,
                                ]) aria-hidden="true"></span>
                            </button>

                            <div
                                x-show="notificacionesAbiertas"
                                x-cloak
                                x-transition.opacity.duration.150ms
                                role="dialog"
                                aria-label="Estado de la caja"
                                class="fixed inset-x-4 top-[4.5rem] z-50 max-h-[calc(100dvh-5rem)] overflow-y-auto overscroll-contain rounded-2xl border border-stone-200 bg-white p-3 shadow-xl dark:border-stone-700 dark:bg-stone-800 sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-[min(22rem,calc(100vw-2rem))]"
                            >
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                        Estado de caja
                                    </p>
                                    <button
                                        type="button"
                                        x-on:click="notificacionesAbiertas = false"
                                        class="rounded-lg p-1 text-stone-400 hover:bg-stone-100 hover:text-stone-700 dark:hover:bg-stone-700 dark:hover:text-stone-200"
                                        aria-label="Cerrar notificaciones"
                                    >
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 6l12 12M18 6 6 18" />
                                        </svg>
                                    </button>
                                </div>

                                <x-resumen-caja :caja="$cajaNav" variante="popover" />
                            </div>
                        </div>

                        <livewire:notificaciones.stock />

                        <button
                            type="button"
                            data-theme-toggle
                            class="rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
                            aria-label="Cambiar entre tema claro y oscuro"
                            title="Cambiar tema"
                        >
                            <svg data-theme-icon-light class="size-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                            </svg>
                            <svg data-theme-icon-dark class="hidden size-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            x-on:click="mobileMenuOpen = ! mobileMenuOpen"
                            x-bind:aria-expanded="mobileMenuOpen ? 'true' : 'false'"
                            x-bind:aria-label="mobileMenuOpen ? 'Cerrar menú' : 'Abrir menú'"
                            aria-controls="mobile-menu"
                            class="inline-flex size-9 items-center justify-center rounded-lg text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white lg:hidden"
                        >
                            <svg x-show="! mobileMenuOpen" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg x-show="mobileMenuOpen" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>

                        <livewire:cuenta.menu-usuario />
                    @endauth
                </div>
            </div>

            @auth
                <nav
                    id="mobile-menu"
                    x-show="mobileMenuOpen"
                    x-cloak
                    x-transition
                    aria-label="Navegación principal"
                    class="border-t border-stone-200 lg:hidden dark:border-stone-700"
                >
                    <div class="mx-auto w-full max-w-6xl space-y-1 px-4 py-3 sm:px-6">
                        <p class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            {{ auth()->user()->name }}
                        </p>

                        <x-nav-link :href="route('ventas.index')" :active="request()->routeIs('ventas.*')" class="block w-full" x-on:click="mobileMenuOpen = false">
                            Ventas
                        </x-nav-link>

                        <x-nav-link :href="route('cajas.index')" :active="request()->routeIs('cajas.*')" class="block w-full" x-on:click="mobileMenuOpen = false">
                            Cajas
                        </x-nav-link>

                        @if (auth()->user()->puedeGestionarConfiguracion())
                            <x-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*')" class="block w-full" x-on:click="mobileMenuOpen = false">
                                Productos
                            </x-nav-link>
                        @endif

                        @can('ver-reportes')
                            <x-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.*')" class="block w-full" x-on:click="mobileMenuOpen = false">
                                Reportes
                            </x-nav-link>
                        @endcan

                        @if (auth()->user()->puedeGestionarConfiguracion())
                            <x-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*')" class="block w-full" x-on:click="mobileMenuOpen = false">
                                Configuración
                            </x-nav-link>
                        @endif
                    </div>
                </nav>
            @endauth
        </header>

        <div class="relative min-h-0 flex-1 overflow-y-auto" data-scroll-principal>
            <div id="cargando-overlay"
                 class="absolute inset-0 z-30 hidden items-center justify-center bg-white/65 backdrop-blur-[1px] dark:bg-stone-900/55"
                 role="status"
                 aria-live="polite"
                 aria-label="Cargando sección">
                <div class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-white/95 px-4 py-3 shadow-lg dark:border-stone-700 dark:bg-stone-800/95">
                    <svg class="size-5 animate-spin text-amber-600 dark:text-amber-400" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-stone-800 dark:text-stone-100">Cargando sección…</p>
                        <p id="cargando-mensaje" class="text-xs text-stone-500 dark:text-stone-400">Preparando la página</p>
                    </div>
                </div>
            </div>

            <main id="contenido-principal" class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
                {{ $slot }}
            </main>
        </div>

        <footer @class([
            'shrink-0 border-t border-stone-200 py-4 text-center text-xs text-stone-500 dark:border-stone-700 dark:text-stone-400',
            'hidden lg:block' => $hideFooterMobile ?? false,
        ])>
            <p>
                &copy; {{ date('Y') }} SofCruz. Todos los derechos reservados.
                <span aria-hidden="true">·</span>
                <button type="button"
                        data-abrir-terminos="#modal-terminos"
                        class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">
                    Términos y privacidad
                </button>
            </p>
        </footer>
    </div>

    <x-toast />
    <x-terminos-modal />

    <script>
        (function () {
            function applyTheme(isDark) {
                document.documentElement.classList.toggle('dark', isDark);
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            }

            document.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-theme-toggle]');
                if (toggle) {
                    applyTheme(!document.documentElement.classList.contains('dark'));
                }
            });

            const overlay = document.getElementById('cargando-overlay');
            const mensaje = document.getElementById('cargando-mensaje');
            const contenido = document.getElementById('contenido-principal');
            let cargando = false;
            let temporizadorSeguridad = null;

            function ocultarCarga() {
                window.clearTimeout(temporizadorSeguridad);

                if (! cargando) {
                    return;
                }

                cargando = false;

                if (overlay) {
                    overlay.classList.add('hidden');
                    overlay.classList.remove('flex');
                }

                if (contenido) {
                    contenido.removeAttribute('aria-busy');
                }
            }

            function mostrarCarga(etiqueta) {
                if (! overlay || cargando) {
                    return;
                }

                cargando = true;
                window.clearTimeout(temporizadorSeguridad);

                if (mensaje) {
                    mensaje.textContent = 'Cargando ' + etiqueta + '…';
                }

                overlay.classList.remove('hidden');
                overlay.classList.add('flex');

                if (contenido) {
                    contenido.setAttribute('aria-busy', 'true');
                }

                temporizadorSeguridad = window.setTimeout(ocultarCarga, 8000);
            }

            document.addEventListener('click', function (event) {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                    return;
                }

                const enlace = event.target.closest('a[href]');

                if (! enlace || enlace.hasAttribute('download') || enlace.target === '_blank' || enlace.hasAttribute('data-no-loading')) {
                    return;
                }

                let destino;

                try {
                    destino = new URL(enlace.href, window.location.href);
                } catch (error) {
                    return;
                }

                if (destino.origin !== window.location.origin) {
                    return;
                }

                if (destino.pathname === window.location.pathname
                    && destino.search === window.location.search
                    && destino.hash) {
                    return;
                }

                if (destino.pathname === window.location.pathname && destino.search === window.location.search) {
                    return;
                }

                const etiqueta = enlace.dataset.cargando
                    || enlace.textContent.replace(/\s+/g, ' ').trim()
                    || 'la sección';

                mostrarCarga(etiqueta);
            });

            window.addEventListener('pageshow', ocultarCarga);
            window.addEventListener('popstate', ocultarCarga);
            document.addEventListener('livewire:navigated', ocultarCarga);
        })();
    </script>
    @livewireScripts
</body>
</html>
