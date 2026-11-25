    @if ($mostrarModalPago)
        <div
            class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 backdrop-blur-sm sm:items-center sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="titulo-pago"
            wire:keydown.escape.window="cerrarModalPago"
            x-on:keydown.enter.window="cobrarConEnter($event)"
        >
            <div class="flex max-h-[92vh] w-full max-w-md flex-col overflow-hidden rounded-t-2xl border border-stone-200 bg-white shadow-xl dark:border-stone-700 dark:bg-stone-800 sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-stone-100 px-5 py-4 dark:border-stone-700/60">
                    <div class="min-w-0">
                        <h2 id="titulo-pago" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                            Cobrar venta
                        </h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400">
                            {{ $cantidadItems }} {{ $cantidadItems === 1 ? 'ítem' : 'ítems' }} en el detalle
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="cerrarModalPago"
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-stone-100 text-stone-600 hover:bg-stone-200 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                        aria-label="Cerrar modal de pago"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 6l12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center dark:border-amber-700/60 dark:bg-amber-900/20">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Total a pagar</p>
                        <p class="text-3xl font-bold text-amber-900 tabular-nums dark:text-amber-200">Bs {{ $totalCarrito }}</p>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Tipo de pago</p>

                        <div class="mt-2 grid grid-cols-2 gap-2" role="group" aria-label="Tipo de pago">
                            <button
                                type="button"
                                wire:click="seleccionarMetodoPago('efectivo')"
                                aria-pressed="{{ $metodoPago === 'efectivo' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-xl border px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                    'border-amber-500 bg-amber-500 text-stone-900' => $metodoPago === 'efectivo',
                                    'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $metodoPago !== 'efectivo',
                                ])
                            >
                                Efectivo
                            </button>
                            <button
                                type="button"
                                wire:click="seleccionarMetodoPago('qr')"
                                aria-pressed="{{ $metodoPago === 'qr' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-xl border px-3 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-500',
                                    'border-amber-500 bg-amber-500 text-stone-900' => $metodoPago === 'qr',
                                    'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $metodoPago !== 'qr',
                                ])
                            >
                                QR
                            </button>
                        </div>

                        @error('metodoPago')
                            <p class="mt-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($metodoPago === 'efectivo')
                        <div class="mt-4">
                            <label for="montoRecibido" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                                Efectivo recibido
                            </label>
                            <input
                                type="number"
                                id="montoRecibido"
                                x-data
                                x-init="$nextTick(() => $el.focus())"
                                wire:model.live.debounce.250ms="montoRecibido"
                                min="0"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="0.00"
                                class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-right text-lg font-semibold text-stone-900 tabular-nums focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
                            >

                            @error('montoRecibido')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror

                            <div class="mt-2 flex items-center justify-between rounded-lg bg-stone-100 px-3 py-2 dark:bg-stone-900/60">
                                <span class="text-sm font-medium text-stone-600 dark:text-stone-300">Cambio a devolver</span>
                                <span class="text-lg font-bold text-emerald-700 tabular-nums dark:text-emerald-400">Bs {{ $cambio }}</span>
                            </div>

                            @if ($montoRecibido !== null && $montoRecibido !== '' && ! $efectivoSuficiente)
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">
                                    El efectivo recibido no cubre el total.
                                </p>
                            @endif
                        </div>
                    @elseif ($metodoPago === 'qr')
                        @if ($negocio->qrPagoUrl())
                            <div class="mt-4 rounded-xl border border-stone-200 bg-stone-50 p-3 dark:border-stone-600 dark:bg-stone-900/40">
                                <p class="text-center text-xs text-stone-600 dark:text-stone-300">
                                    Escanea el QR para pagar <strong class="font-semibold">Bs {{ $totalCarrito }}</strong>.
                                </p>

                                <button
                                    type="button"
                                    x-on:click="qrAmpliado = true"
                                    class="mx-auto mt-3 block rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    aria-label="Ampliar QR de pago"
                                >
                                    <img
                                        src="{{ $negocio->qrPagoUrl() }}"
                                        alt="QR de pago de {{ $negocio->nombre_comercial }}"
                                        class="mx-auto h-48 w-48 rounded-lg bg-white object-contain p-2 ring-1 ring-stone-200 dark:ring-stone-600"
                                    >
                                </button>

                                <p class="mt-2 text-center text-[11px] text-stone-400 dark:text-stone-500">
                                    Toca la imagen para ampliarla.
                                </p>
                            </div>
                        @else
                            <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-700/60 dark:bg-amber-900/20 dark:text-amber-300" role="status">
                                Este negocio no tiene un QR configurado. Puedes registrar el cobro igualmente o pedir al administrador que lo agregue en Configuración.
                            </p>
                        @endif
                    @else
                        <p class="mt-4 text-xs text-stone-400 dark:text-stone-500">
                            Selecciona cómo pagará el cliente para continuar.
                        </p>
                    @endif

                    <div class="mt-4">
                        <label
                            for="paraLlevar"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-stone-300 bg-white p-3 transition hover:border-amber-400 hover:bg-amber-50/50 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:border-stone-600 dark:bg-stone-900/40 dark:hover:border-amber-500/60 dark:hover:bg-amber-900/10 dark:has-[:checked]:border-emerald-500/70 dark:has-[:checked]:bg-emerald-900/20"
                        >
                            <input
                                type="checkbox"
                                id="paraLlevar"
                                wire:model.live="paraLlevar"
                                class="peer sr-only"
                            >
                            <span
                                class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-md border-2 border-stone-300 bg-white text-white transition peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 peer-focus-visible:ring-offset-2 [&>svg]:opacity-0 peer-checked:[&>svg]:opacity-100 dark:border-stone-500 dark:bg-stone-800 dark:peer-checked:border-emerald-500 dark:peer-checked:bg-emerald-500 dark:peer-focus-visible:ring-offset-stone-800"
                                aria-hidden="true"
                            >
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m5 12 5 5L20 7" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-stone-900 dark:text-stone-100">Para llevar</span>
                                <span class="mt-0.5 block text-xs text-stone-500 dark:text-stone-400">
                                    La comida no se consumirá dentro del local.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="mt-4">
                        <label for="glosa" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            Glosa <span class="font-normal normal-case">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            id="glosa"
                            wire:model="glosa"
                            maxlength="255"
                            placeholder="Ej.: para llevar, sin cebolla…"
                            class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:placeholder:text-stone-500"
                        >

                        @error('glosa')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-3">
                        <label for="cliente" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                            Cliente <span class="font-normal normal-case">(opcional)</span>
                        </label>
                        <input
                            type="text"
                            id="cliente"
                            wire:model="cliente"
                            maxlength="120"
                            placeholder="Ej.: Juan Pérez"
                            class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm text-stone-900 placeholder:text-stone-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:placeholder:text-stone-500"
                        >

                        @error('cliente')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="border-t border-stone-100 px-5 py-4 dark:border-stone-700/60">
                    @error('carrito')
                        <p class="mb-2 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror

                    <p class="mb-2 text-[11px] text-stone-400 dark:text-stone-500">
                        Presiona <kbd class="rounded border border-stone-300 px-1 font-semibold dark:border-stone-600">Enter</kbd> para registrar la venta.
                    </p>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            wire:click="cerrarModalPago"
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700"
                        >
                            Volver
                        </button>
                        <button
                            type="button"
                            wire:click="cobrar"
                            wire:loading.attr="disabled"
                            wire:target="cobrar"
                            aria-keyshortcuts="Enter"
                            title="Presiona Enter para registrar la venta"
                            @disabled(! $puedeCobrar)
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-40 dark:focus:ring-offset-stone-800"
                        >
                            Cobrar Bs {{ $totalCarrito }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="qrAmpliado"
            x-transition.opacity
            class="fixed inset-0 z-[60] flex items-center justify-center bg-stone-900/80 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="QR de pago ampliado"
            x-on:click.self="qrAmpliado = false"
        >
            <div class="relative w-full max-w-lg rounded-2xl bg-white p-4 shadow-xl dark:bg-stone-800">
                <button
                    type="button"
                    x-on:click="qrAmpliado = false"
                    class="absolute right-2 top-2 flex size-9 items-center justify-center rounded-full bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-300 dark:hover:bg-stone-600"
                    aria-label="Cerrar QR ampliado"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>

                @if ($negocio !== null && $negocio->qrPagoUrl())
                    <img
                        src="{{ $negocio->qrPagoUrl() }}"
                        alt="QR de pago ampliado de {{ $negocio->nombre_comercial }}"
                        class="mx-auto max-h-[70vh] w-full rounded-lg bg-white object-contain p-2"
                    >

                    <p class="mt-2 text-center text-sm font-semibold text-stone-800 dark:text-stone-100">
                        Paga Bs {{ $totalCarrito }} escaneando este QR
                    </p>
                @endif
            </div>
        </div>

    @endif
