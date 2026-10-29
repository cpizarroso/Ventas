<x-layouts.legal
    :negocio="$negocio"
    :nombre-comercial="$nombreComercial"
    title="Términos y privacidad"
>
    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-8 dark:border-stone-700 dark:bg-stone-800">
        @include('terminos._documento')
    </div>

    <p class="mt-6 text-center text-xs text-stone-500 dark:text-stone-400">
        Si tienes dudas sobre este documento, escríbenos a
        <a href="mailto:{{ $contacto }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">{{ $contacto }}</a>
        antes de aceptar.
    </p>
</x-layouts.legal>
