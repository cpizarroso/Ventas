<x-layouts.app>
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
            Identidad del negocio, usuarios y categorías de venta.
        </p>
        <a href="{{ route('configuracion.aceptaciones.index') }}"
           class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 underline decoration-amber-300 underline-offset-4 hover:text-amber-800 dark:text-amber-400 dark:decoration-amber-700 dark:hover:text-amber-300">
            Ver registro de aceptación de términos
        </a>
    </div>

    @if (auth()->user()->negocio === null)
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Sin negocio asignado</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                Tu usuario no tiene un negocio asignado, por lo que no hay configuración disponible.
            </p>
        </div>
    @else
        <div class="space-y-8">
            <livewire:configuracion.identidad-negocio />
            <livewire:configuracion.usuarios-admin />
            <livewire:configuracion.categorias-admin />
        </div>
    @endif
</x-layouts.app>
