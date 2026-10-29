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
                        <div class="relative">
                            <input id="password"
                                   name="password"
                                   type="password"
                                   required
                                   autocomplete="current-password"
                                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 pr-11 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                            <button type="button"
                                    data-toggle-password="password"
                                    aria-controls="password"
                                    aria-label="Mostrar contraseña"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-stone-500 transition hover:text-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:text-stone-100">
                                <svg data-password-icon-show class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-password-icon-hide class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.58 10.58a3 3 0 0 0 4.24 4.24" />
                                    <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a18.45 18.45 0 0 1-2.06 3.19" />
                                    <path d="M6.61 6.61A18.45 18.45 0 0 0 2 12s3.5 7 10 7a10.94 10.94 0 0 0 5.39-1.39" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-stone-600 dark:text-stone-300">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                        Recordarme
                    </label>

                    @php($requiereAceptarTerminos = session('requiere_aceptar_terminos') || old('aceptar_terminos') || $errors->has('aceptar_terminos'))

                    @if ($requiereAceptarTerminos)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/30">
                            <div class="flex items-start gap-2">
                                <input id="aceptar_terminos"
                                       name="aceptar_terminos"
                                       type="checkbox"
                                       value="1"
                                       @checked(old('aceptar_terminos'))
                                       required
                                       class="mt-0.5 size-4 shrink-0 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                                <label for="aceptar_terminos" class="text-sm text-stone-700 dark:text-stone-300">
                                    He leído y acepto los Términos y Condiciones y la Política de Privacidad,
                                    versión {{ config('jibas.terminos.version') }}, vigente desde el
                                    {{ \Illuminate\Support\Carbon::parse(config('jibas.terminos.vigente_desde'))->format('d/m/Y') }}.
                                </label>
                            </div>
                            @error('aceptar_terminos')
                                <p class="mt-2 pl-6 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <p class="text-center text-xs text-stone-500 dark:text-stone-400">
                        <button type="button"
                                data-abrir-terminos="#modal-terminos"
                                class="font-semibold text-amber-700 underline decoration-amber-300 underline-offset-4 hover:text-amber-800 dark:text-amber-400 dark:decoration-amber-700 dark:hover:text-amber-300">
                            Ver términos y condiciones
                        </button>
                    </p>

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

    <x-terminos-modal />

    <script>
        (function () {
            document.addEventListener('click', function (event) {
                const toggle = event.target.closest('[data-toggle-password]');

                if (! toggle) {
                    return;
                }

                const input = document.getElementById(toggle.getAttribute('data-toggle-password'));

                if (! input) {
                    return;
                }

                const mostrar = input.type === 'password';

                input.type = mostrar ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
                toggle.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');

                const iconoMostrar = toggle.querySelector('[data-password-icon-show]');
                const iconoOcultar = toggle.querySelector('[data-password-icon-hide]');

                if (iconoMostrar && iconoOcultar) {
                    iconoMostrar.classList.toggle('hidden', mostrar);
                    iconoOcultar.classList.toggle('hidden', ! mostrar);
                }
            });
        })();
    </script>
</body>
</html>
