    @if ($ventaParaImprimirId !== null)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-venta-registrada"
            wire:keydown.escape.window="omitirImpresion"
        >
            <div class="w-full max-w-sm rounded-2xl border border-stone-200 bg-white p-5 shadow-xl dark:border-stone-700 dark:bg-stone-800 sm:p-6">
                <div class="flex size-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 9V4h12v5" />
                        <path d="M6 18H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-2" />
                        <path d="M6 14h12v6H6z" />
                    </svg>
                </div>

                <h2 id="titulo-venta-registrada" class="mt-4 text-lg font-semibold text-stone-900 dark:text-stone-100">
                    Venta #{{ $ventaParaImprimirNumero ?? $ventaParaImprimirId }} registrada
                </h2>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                    ¿Deseas imprimir el detalle de esta venta?
                </p>
                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">
                    La venta ya quedó guardada. Imprimir el ticket es opcional.
                </p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        wire:click="omitirImpresion"
                        class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                    >
                        Ahora no
                    </button>
                    <a
                        href="{{ route('ventas.ticket', ['venta' => $ventaParaImprimirId, 'imprimir' => 1]) }}"
                        target="_blank"
                        rel="noopener"
                        wire:click="omitirImpresion"
                        class="rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                    >
                        Imprimir ticket
                    </a>
                </div>
            </div>
        </div>
    @endif
