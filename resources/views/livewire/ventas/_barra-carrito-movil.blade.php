        @if ($cantidadItems > 0)
            <div class="sticky bottom-0 z-30 -mx-4 border-t border-stone-200 bg-white/95 px-4 py-3 backdrop-blur dark:border-stone-700 dark:bg-stone-800/95 sm:-mx-6 sm:px-6 lg:hidden">
                <div class="mx-auto flex w-full max-w-2xl items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-2">
                        <button
                            type="button"
                            wire:click="pedirCancelacion"
                            class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-xl border border-stone-300 px-3 text-xs font-semibold text-stone-600 hover:border-red-300 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 dark:border-stone-600 dark:text-stone-300 dark:hover:border-red-800 dark:hover:bg-red-900/20 dark:hover:text-red-400"
                            aria-label="Cancelar venta"
                            title="Cancelar venta"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-4" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                            Cancelar
                        </button>
                        <div class="min-w-0">
                            <p class="truncate text-xs font-medium text-stone-500 dark:text-stone-400">
                                {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} en el detalle
                            </p>
                            <p class="text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">Bs {{ $totalCarrito }}</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        x-on:click="carritoAbierto = true"
                        class="shrink-0 rounded-xl bg-amber-500 px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                    >
                        Ver detalle
                    </button>
                </div>
            </div>
        @endif
