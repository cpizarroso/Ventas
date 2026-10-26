<x-layouts.legal
    :negocio="$negocio"
    :nombre-comercial="$nombreComercial"
    title="Términos y privacidad"
>
    @if ($requiereAceptacion)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200" role="status">
            <p class="font-semibold">Debes aceptar la versión vigente para continuar.</p>
            <p class="mt-1">Revisa el documento y confirma tu aceptación.</p>
            <a href="{{ route('terminos.aceptar') }}"
               class="mt-3 inline-flex rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                Ir a aceptar
            </a>
        </div>
    @endif

    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-8 dark:border-stone-700 dark:bg-stone-800">
        @include('terminos._documento')
    </div>

    <p class="mt-6 text-center text-xs text-stone-500 dark:text-stone-400">
        Si tienes dudas sobre este documento, escríbenos a
        <a href="mailto:{{ $contacto }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">{{ $contacto }}</a>
        antes de aceptar.
    </p>
</x-layouts.legal>
