<div
    role="dialog"
    aria-modal="true"
    aria-labelledby="hoja-producto-titulo-{{ $producto->id }}"
    x-show="hojaAbierta"
    x-cloak
    x-on:keydown.escape.window="hojaAbierta = false"
    x-transition:enter="transition duration-200 ease-out"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition duration-150 ease-in"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[80] md:hidden"
>
    <div class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm" x-on:click="hojaAbierta = false" aria-hidden="true"></div>

    <div class="absolute inset-x-0 bottom-0 mx-auto w-full max-w-md rounded-t-2xl border border-stone-200 bg-white p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] shadow-2xl dark:border-stone-700 dark:bg-stone-800">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h3 id="hoja-producto-titulo-{{ $producto->id }}" class="text-sm font-bold text-stone-900 dark:text-stone-50">
                Detalle del producto
            </h3>
            <button
                type="button"
                x-ref="cerrarHoja"
                x-on:click="hojaAbierta = false"
                aria-label="Cerrar detalle del producto"
                class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg border border-stone-300 text-stone-500 hover:bg-stone-100 hover:text-stone-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-stone-200"
            >
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        @include('livewire.ventas._ficha-producto', ['producto' => $producto])
    </div>
</div>
