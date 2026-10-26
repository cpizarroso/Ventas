@props([
    'title' => 'Términos y privacidad',
    'negocio' => null,
    'nombreComercial' => null,
])

@php
    $nombreComercial = $nombreComercial ?? $negocio?->nombre_comercial ?? config('app.name');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} · {{ $nombreComercial }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="min-h-full bg-stone-100 font-sans text-stone-900 dark:bg-stone-900 dark:text-stone-100">
    <div class="flex min-h-full flex-col">
        <header class="border-b border-stone-200 bg-white/90 backdrop-blur dark:border-stone-700 dark:bg-stone-800/90">
            <div class="mx-auto flex w-full max-w-4xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <a href="{{ url('/') }}"
                   class="-m-1 flex min-w-0 items-center gap-3 rounded-lg p-1 hover:bg-stone-100 dark:hover:bg-stone-700/60">
                    @if ($negocio?->logo)
                        <img src="{{ asset('storage/' . $negocio->logo) }}"
                             alt="Logo de {{ $nombreComercial }}"
                             class="size-9 shrink-0 rounded-xl object-cover ring-1 ring-stone-200 dark:ring-stone-600">
                    @else
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" aria-hidden="true">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l1.5-5h15L21 9" />
                                <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                                <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                                <path d="M9 20v-5h6v5" />
                            </svg>
                        </div>
                    @endif
                    <span class="truncate text-sm font-semibold">{{ $nombreComercial }}</span>
                </a>

                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                            Cerrar sesión
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                        Iniciar sesión
                    </a>
                @endauth
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6 sm:py-12">
            {{ $slot }}
        </main>

        <footer class="border-t border-stone-200 py-4 text-center text-xs text-stone-500 dark:border-stone-700 dark:text-stone-400">
            <p>
                &copy; {{ date('Y') }} SofCruz. Todos los derechos reservados.
                <span aria-hidden="true">·</span>
                <a href="{{ route('terminos.index') }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">
                    Términos y privacidad
                </a>
            </p>
        </footer>
    </div>
</body>
</html>
