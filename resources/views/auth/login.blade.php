<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Iniciar sesión · {{ $nombreComercial }}</title>
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
<body class="flex min-h-full flex-col bg-stone-100 font-sans dark:bg-stone-900">
    <div class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                @if ($negocio?->logo)
                    <img src="{{ asset('storage/' . $negocio->logo) }}"
                         alt="Logo de {{ $nombreComercial }}"
                         class="mx-auto mb-3 block size-14 rounded-2xl object-cover ring-1 ring-stone-200 dark:ring-stone-600">
                @else
                    <div class="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300" aria-hidden="true">
                        <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l1.5-5h15L21 9" />
                            <path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0" />
                            <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                            <path d="M9 20v-5h6v5" />
                        </svg>
                    </div>
                @endif
                <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100">{{ $nombreComercial }}</h1>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">Inicia sesión para continuar</p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">Usuario o correo electrónico</label>
                        <input id="login"
                               name="login"
                               type="text"
                               value="{{ old('login') }}"
                               required
                               autofocus
                               autocomplete="username"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">Contraseña</label>
                        <input id="password"
                               name="password"
                               type="password"
                               required
                               autocomplete="current-password"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
                        <input name="remember" type="checkbox" value="1" class="size-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                        Recordarme
                    </label>

                    <button type="submit"
                            class="w-full rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                        Iniciar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="px-4 pb-6 text-center text-xs text-stone-500 dark:text-stone-400">
        <p>&copy; {{ date('Y') }} SofCruz. Todos los derechos reservados.</p>
        <p class="mt-1">
            <a href="{{ route('terminos.index') }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">
                Términos y privacidad
            </a>
        </p>
    </footer>
</body>
</html>
