<x-layouts.app>
    <div class="mb-6">
        <a href="{{ route('configuracion.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
            </svg>
            Volver a configuración
        </a>
        <h1 class="mt-2 text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Registro de aceptación de términos</h1>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
            Consulta qué usuarios aceptaron los términos y condiciones, junto con la versión, fecha, IP y navegador.
        </p>
    </div>

    <livewire:configuracion.registro-aceptaciones-terminos />
</x-layouts.app>
