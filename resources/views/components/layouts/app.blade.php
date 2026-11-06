<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('Ventas') }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
            x-data="{ mobileMenuOpen: false }"
            x-on:keydown.escape.window="mobileMenuOpen = false"
            x-on:click.outside="mobileMenuOpen = false"
            x-on:resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false"
            class="z-20 shrink-0 border-b border-stone-200 bg-white/90 backdrop-blur dark:border-stone-700 dark:bg-stone-800/90"
        >
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    @auth
                        <a href="{{ route('ventas.index') }}"
                           class="flex min-w-0 items-center gap-3 rounded-lg -m-1 p-1 hover:bg-stone-100 dark:hover:bg-stone-700/60"
                           aria-label="Ir a la pantalla principal de ventas">
                            @if (auth()->user()->negocio)
                                @if (auth()->user()->negocio->logoUrl())
                                    <img src="{{ auth()->user()->negocio->logoUrl() }}"
                                         alt="Logo de {{ auth()->user()->negocio->nombre_comercial }}"
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
                                        {{ auth()->user()->negocio->nombre_comercial }}
                                    </p>
                                    @if (auth()->user()->negocio->eslogan)
                                        <p class="truncate text-xs text-stone-500 dark:text-stone-400">
                                            {{ auth()->user()->negocio->eslogan }}
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
                            <x-nav-link :href="route('ventas.index')" :active="request()->routeIs('ventas.*')">
                                Ventas
                            </x-nav-link>

                            @can('ver-reportes')
                                <x-nav-link :href="route('reportes.index')" :active="request()->routeIs('reportes.*')">
                                    Reportes
                                </x-nav-link>
                            @endcan

                            @if (auth()->user()->puedeGestionarConfiguracion())
                                <x-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*')">
                                    Configuración
                                </x-nav-link>
                            @endif
                        </div>

                        <div
                            class="flex min-w-0 shrink items-center gap-2 rounded-full border border-stone-200 bg-stone-50 py-1 pl-1 pr-2.5 dark:border-stone-700 dark:bg-stone-700/40"
                            title="{{ auth()->user()->name }}"
                        >
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-xs font-bold uppercase text-stone-900" aria-hidden="true">
                                {{ mb_substr(trim(auth()->user()->name), 0, 1) }}
                            </span>
                            <span class="max-w-20 truncate text-xs font-semibold text-stone-700 dark:text-stone-200 sm:max-w-40 sm:text-sm">
                                <span class="sr-only">Usuario: </span>{{ auth()->user()->name }}
                            </span>
                        </div>

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

                        <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                                Cerrar sesión
                            </button>
                        </form>

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

                        <form method="POST" action="{{ route('logout') }}" class="pt-1">
                            @csrf
                            <button type="submit"
                                    class="w-full rounded-lg px-3 py-2 text-left text-sm font-medium text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </nav>
            @endauth
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
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
                <a href="{{ route('terminos.index') }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">
                    Términos y privacidad
                </a>
            </p>
        </footer>
    </div>

    <x-toast />

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
        })();
    </script>
</body>
</html>
