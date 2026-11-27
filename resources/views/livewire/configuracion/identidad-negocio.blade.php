<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="guardar" class="space-y-5">
        <div class="grid gap-5 lg:grid-cols-2">
            <section class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-5">
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Identidad visual del negocio</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    El nombre comercial es obligatorio. El eslogan y el logo son opcionales.
                </p>

                <div class="mt-3 space-y-4">
                    <div class="flex flex-col items-center gap-2">
                        <div class="relative">
                            @if ($logo_nuevo && $logo_nuevo->isPreviewable())
                                <button type="button"
                                        wire:click="abrirImagenLogo"
                                        data-preservar-scroll
                                        title="Ampliar logo"
                                        aria-label="Ampliar logo"
                                        class="group relative flex size-16 items-center justify-center overflow-hidden rounded-lg ring-2 ring-amber-400 transition-all hover:scale-105 focus-visible:ring-2 focus-visible:ring-amber-500">
                                    <img src="{{ $logo_nuevo->temporaryUrl() }}"
                                         alt="Vista previa del logo seleccionado"
                                         class="size-full rounded-xl object-cover">
                                    <span class="absolute inset-0 flex items-center justify-center bg-stone-900/40 text-white opacity-0 transition-opacity group-hover:opacity-100">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            <path d="M10.5 7.5v6m-3-3h6" />
                                        </svg>
                                    </span>
                                </button>
                            @elseif ($logo_actual)
                                <button type="button"
                                        wire:click="abrirImagenLogo"
                                        data-preservar-scroll
                                        title="Ampliar logo"
                                        aria-label="Ampliar logo"
                                        class="group relative flex size-16 items-center justify-center overflow-hidden rounded-lg ring-1 ring-stone-200 transition-all hover:scale-105 hover:ring-amber-400 focus-visible:ring-2 focus-visible:ring-amber-500 dark:ring-stone-600">
                                    <img src="{{ $urlLogoActual }}"
                                         alt="Logo de {{ $nombre_comercial }}"
                                         class="size-full rounded-xl object-cover">
                                    <span class="absolute inset-0 flex items-center justify-center bg-stone-900/40 text-white opacity-0 transition-opacity group-hover:opacity-100">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            <path d="M10.5 7.5v6m-3-3h6" />
                                        </svg>
                                    </span>
                                </button>
                            @else
                                <div class="flex size-16 items-center justify-center rounded-xl bg-stone-100 text-stone-400 ring-1 ring-stone-200 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600" aria-hidden="true">
                                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 9l1.5-5h15L21 9" />
                                        <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                                        <path d="M9 20v-5h6v5" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <label class="cursor-pointer rounded-lg bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600">
                            Elegir logo…
                            <input type="file" wire:model="logo_nuevo" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>

                        @error('logo_nuevo')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror

                        @if ($logo_actual && ! $logo_nuevo && ! $confirmarEliminacionLogo)
                            <button type="button"
                                    wire:click="$set('confirmarEliminacionLogo', true)"
                                    class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                Quitar logo
                            </button>
                        @endif

                        @if ($confirmarEliminacionLogo)
                            <div class="rounded-xl border border-red-200 bg-red-50 p-2 text-center dark:border-red-900 dark:bg-red-900/30">
                                <p class="text-xs font-medium text-red-800 dark:text-red-300">¿Quitar el logo actual?</p>
                                <div class="mt-2 flex justify-center gap-2">
                                    <button type="button"
                                            wire:click="eliminarLogo"
                                            class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-500">
                                        Sí, quitar
                                    </button>
                                    <button type="button"
                                            wire:click="$set('confirmarEliminacionLogo', false)"
                                            class="rounded-lg bg-stone-200 px-2.5 py-1 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        @endif

                        <p class="max-w-32 text-center text-[11px] leading-tight text-stone-400 dark:text-stone-500">
                            PNG, JPG o WebP. Máx. 5 MB. Se optimiza automáticamente (máx. 512 px).
                        </p>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="nombre_comercial" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                Nombre comercial <span class="text-red-500" aria-hidden="true">*</span>
                            </label>
                            <input id="nombre_comercial"
                                   type="text"
                                   wire:model="nombre_comercial"
                                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500"
                                   placeholder="Ej: Mi tienda">
                            @error('nombre_comercial')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="eslogan" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                Eslogan <span class="text-xs font-normal text-stone-400">(opcional)</span>
                            </label>
                            <input id="eslogan"
                                   type="text"
                                   wire:model="eslogan"
                                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500"
                                   placeholder="Ej: Calidad y buen precio">
                            @error('eslogan')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <p class="mb-1 text-sm font-medium text-stone-700 dark:text-stone-300">
                                Método de pago por defecto
                            </p>
                            <div class="grid grid-cols-2 gap-2" role="group" aria-label="Método de pago por defecto">
                                @foreach ([\App\Enums\MetodoPago::Efectivo, \App\Enums\MetodoPago::Qr] as $metodoPago)
                                    <label @class([
                                        'cursor-pointer rounded-xl border px-3 py-2 text-center text-sm font-semibold transition',
                                        'border-amber-500 bg-amber-500 text-stone-900' => $metodo_pago_predeterminado === $metodoPago->value,
                                        'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700' => $metodo_pago_predeterminado !== $metodoPago->value,
                                    ])>
                                        <input type="radio"
                                               wire:model.live="metodo_pago_predeterminado"
                                               value="{{ $metodoPago->value }}"
                                               class="sr-only">
                                        {{ $metodoPago->etiqueta() }}
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-[11px] text-stone-400 dark:text-stone-500">
                                Se preseleccionará al abrir el modal de cobro.
                            </p>
                            @error('metodo_pago_predeterminado')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <p class="mb-1 text-sm font-medium text-stone-700 dark:text-stone-300">
                                Imprimir ticket por defecto
                            </p>
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:hover:bg-stone-800">
                                <input type="checkbox"
                                       wire:model="imprimir_ticket_predeterminado"
                                       class="size-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                                Resaltar «Imprimir ticket» al registrar la venta
                            </label>
                            <p class="mt-1 text-[11px] text-stone-400 dark:text-stone-500">
                                Si no está activo, la opción «Ahora no» quedará seleccionada por defecto. El modal siempre se mostrará.
                            </p>
                        </div>

                        <div>
                            <label for="stock-min" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                                Stock mínimo por defecto
                            </label>
                            <input id="stock-min"
                                   type="number"
                                   step="1"
                                   min="0"
                                   max="999999"
                                   wire:model="stock_min"
                                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-1.5 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500">
                            <p class="mt-1 text-[11px] text-stone-400 dark:text-stone-500">
                                Se sugerirá al crear un producto. Cada producto puede tener un valor propio.
                            </p>
                            @error('stock_min')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <p class="mb-1 text-sm font-medium text-stone-700 dark:text-stone-300">
                                Stock negativo
                            </p>
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:hover:bg-stone-800">
                                <input type="checkbox"
                                       wire:model="permitir_stock_negativo"
                                       class="size-4 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                                Permitir vender con stock negativo
                            </label>
                            <p class="mt-1 text-[11px] text-stone-400 dark:text-stone-500">
                                Si está activo, la venta avisará pero podrá cobrarse aunque el stock quede negativo. Si no, se bloqueará el cobro hasta ajustar la cantidad.
                            </p>
                            @error('permitir_stock_negativo')
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="submit"
                            class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                        Guardar cambios
                    </button>
                </div>
            </section>

            <section class="min-w-0 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-5">
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">QR de pago</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Esta imagen se mostrará al cajero cuando seleccione QR como forma de pago.
                </p>

                <div class="mt-3 space-y-4">
                    <div class="flex flex-col items-center gap-2">
                        <div class="relative">
                            @if ($qr_nuevo && $qr_nuevo->isPreviewable())
                                <button type="button"
                                        wire:click="abrirImagenQr"
                                        data-preservar-scroll
                                        title="Ampliar QR de pago"
                                        aria-label="Ampliar QR de pago"
                                        class="group relative flex size-24 items-center justify-center overflow-hidden rounded-lg bg-white p-2 ring-2 ring-amber-400 transition-all hover:scale-105 focus-visible:ring-2 focus-visible:ring-amber-500">
                                    <img src="{{ $qr_nuevo->temporaryUrl() }}"
                                         alt="Vista previa del QR de pago seleccionado"
                                         class="size-full rounded-xl object-contain">
                                    <span class="absolute inset-0 flex items-center justify-center rounded-lg bg-stone-900/40 text-white opacity-0 transition-opacity group-hover:opacity-100">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            <path d="M10.5 7.5v6m-3-3h6" />
                                        </svg>
                                    </span>
                                </button>
                            @elseif ($qr_actual)
                                <button type="button"
                                        wire:click="abrirImagenQr"
                                        data-preservar-scroll
                                        title="Ampliar QR de pago"
                                        aria-label="Ampliar QR de pago"
                                        class="group relative flex size-24 items-center justify-center overflow-hidden rounded-lg bg-white p-2 ring-1 ring-stone-200 transition-all hover:scale-105 hover:ring-amber-400 focus-visible:ring-2 focus-visible:ring-amber-500 dark:ring-stone-600">
                                    <img src="{{ $urlQrActual }}"
                                         alt="QR de pago de {{ $nombre_comercial }}"
                                         class="size-full rounded-xl object-contain">
                                    <span class="absolute inset-0 flex items-center justify-center rounded-lg bg-stone-900/40 text-white opacity-0 transition-opacity group-hover:opacity-100">
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                            <path d="M10.5 7.5v6m-3-3h6" />
                                        </svg>
                                    </span>
                                </button>
                            @else
                                <div class="flex size-24 items-center justify-center rounded-xl bg-stone-100 text-stone-400 ring-1 ring-stone-200 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600" aria-hidden="true">
                                    <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="3" width="7" height="7" rx="1" />
                                        <rect x="14" y="3" width="7" height="7" rx="1" />
                                        <rect x="3" y="14" width="7" height="7" rx="1" />
                                        <path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <label class="cursor-pointer rounded-lg bg-stone-100 px-2.5 py-1 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600">
                            Elegir QR…
                            <input type="file" wire:model="qr_nuevo" accept="image/png,image/jpeg,image/webp" class="hidden">
                        </label>

                        @error('qr_nuevo')
                            <p class="text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror

                        @if ($qr_actual && ! $qr_nuevo && ! $confirmarEliminacionQr)
                            <button type="button"
                                    wire:click="$set('confirmarEliminacionQr', true)"
                                    class="text-xs font-medium text-red-600 hover:underline dark:text-red-400">
                                Quitar QR
                            </button>
                        @endif

                        @if ($confirmarEliminacionQr)
                            <div class="rounded-xl border border-red-200 bg-red-50 p-2 text-center dark:border-red-900 dark:bg-red-900/30">
                                <p class="text-xs font-medium text-red-800 dark:text-red-300">¿Quitar el QR actual?</p>
                                <div class="mt-2 flex justify-center gap-2">
                                    <button type="button"
                                            wire:click="eliminarQr"
                                            class="rounded-lg bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-500">
                                        Sí, quitar
                                    </button>
                                    <button type="button"
                                            wire:click="$set('confirmarEliminacionQr', false)"
                                            class="rounded-lg bg-stone-200 px-2.5 py-1 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        @endif

                        <p class="max-w-40 text-center text-[11px] leading-tight text-stone-400 dark:text-stone-500">
                            PNG, JPG o WebP. Máx. 5 MB. Se guarda sin pérdida de calidad.
                        </p>
                    </div>

                    <div class="space-y-2 text-sm text-stone-500 dark:text-stone-400">
                        <p>Sube la imagen del QR que usas para cobrar pagos por transferencia.</p>
                        <p>Usa una imagen cuadrada, nítida y con el código completo para que el cliente pueda escanearla sin problemas.</p>
                        <p class="text-xs text-stone-400 dark:text-stone-500">
                            Si no hay un QR configurado, el cajero verá un aviso pero podrá registrar el cobro igualmente.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </form>

    <x-modal-configuracion id="modal-imagen-identidad"
                           :titulo="$tituloImagenAmpliada"
                           descripcion="Vista ampliada de la imagen configurada."
                           cerrar="cerrarImagenAmpliada"
                           ancho="max-w-3xl"
                           :abierto="$urlImagenAmpliada !== null">
        @if ($urlImagenAmpliada)
            <img src="{{ $urlImagenAmpliada }}"
                 alt="Imagen ampliada: {{ $tituloImagenAmpliada }}"
                 class="{{ $imagenAmpliada === 'qr'
                     ? 'mx-auto aspect-square w-4/5 max-h-[70vh] max-w-xs rounded-xl bg-white object-contain p-2 sm:max-w-sm sm:p-3 md:max-w-md lg:max-w-lg xl:max-w-xl'
                     : 'mx-auto max-h-[70vh] w-auto max-w-full rounded-xl object-contain' }}">
        @endif
    </x-modal-configuracion>
</div>
