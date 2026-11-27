        @if ($mostrarModalSinCaja)
            <div class="fixed inset-0 z-[70] flex items-end justify-center bg-stone-900/60 p-4 backdrop-blur-sm sm:items-center"
                 role="alertdialog"
                 aria-modal="true"
                 aria-labelledby="sin-caja-titulo"
                 aria-describedby="sin-caja-descripcion"
                 wire:keydown.escape.window="cerrarModalSinCaja">
                <div class="absolute inset-0" wire:click="cerrarModalSinCaja" aria-hidden="true"></div>

                <div class="relative w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-stone-800 sm:p-6">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="size-5" aria-hidden="true">
                                <rect x="3" y="6" width="18" height="13" rx="2" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2M3 12h18M9 12v3" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h3 id="sin-caja-titulo" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                                No tienes una caja abierta
                            </h3>
                            <p id="sin-caja-descripcion" class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                                Abre tu caja para poder agregar productos y cobrar ventas.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="cerrarModalSinCaja"
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                        >
                            Entendido
                        </button>
                        <button
                            type="button"
                            wire:click="abrirNotificacionesCaja"
                            class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                        >
                            Abrir caja
                        </button>
                    </div>
                </div>
            </div>
        @endif
