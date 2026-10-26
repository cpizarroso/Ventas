<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="guardar" class="space-y-6">
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Identidad visual del negocio</h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                El nombre comercial es obligatorio. El eslogan y el logo son opcionales.
            </p>

            <div class="mt-5 grid gap-5 sm:grid-cols-[auto,1fr] sm:items-start">
                <div class="flex flex-col items-center gap-2">
                    <div class="relative">
                        @if ($logo_nuevo && $logo_nuevo->isPreviewable())
                            <img src="{{ $logo_nuevo->temporaryUrl() }}"
                                 alt="Vista previa del logo seleccionado"
                                 class="size-24 rounded-2xl object-cover ring-2 ring-amber-400 dark:ring-amber-500/60">
                        @elseif ($logo_actual)
                            <img src="{{ asset('storage/' . $logo_actual) }}"
                                 alt="Logo de {{ $nombre_comercial }}"
                                 class="size-24 rounded-2xl object-cover ring-1 ring-stone-200 dark:ring-stone-600">
                        @else
                            <div class="flex size-24 items-center justify-center rounded-2xl bg-stone-100 text-stone-400 ring-1 ring-stone-200 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600" aria-hidden="true">
                                <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l1.5-5h15L21 9" />
                                    <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                                    <path d="M9 20v-5h6v5" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <label class="cursor-pointer rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600">
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
                        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-center dark:border-red-900 dark:bg-red-900/30">
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
                        PNG, JPG o WebP. Máx. 2 MB.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="nombre_comercial" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">
                            Nombre comercial <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input id="nombre_comercial"
                               type="text"
                               wire:model="nombre_comercial"
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500"
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
                               class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100 dark:focus:border-amber-500"
                               placeholder="Ej: Calidad y buen precio">
                        @error('eslogan')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">QR de pago</h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                Esta imagen se mostrará al cajero cuando seleccione QR como forma de pago.
            </p>

            <div class="mt-5 grid gap-5 sm:grid-cols-[auto,1fr] sm:items-start">
                <div class="flex flex-col items-center gap-2">
                    <div class="relative">
                        @if ($qr_nuevo && $qr_nuevo->isPreviewable())
                            <img src="{{ $qr_nuevo->temporaryUrl() }}"
                                 alt="Vista previa del QR de pago seleccionado"
                                 class="size-40 rounded-2xl bg-white object-contain p-2 ring-2 ring-amber-400 dark:ring-amber-500/60">
                        @elseif ($qr_actual)
                            <img src="{{ asset('storage/' . $qr_actual) }}"
                                 alt="QR de pago de {{ $nombre_comercial }}"
                                 class="size-40 rounded-2xl bg-white object-contain p-2 ring-1 ring-stone-200 dark:ring-stone-600">
                        @else
                            <div class="flex size-40 items-center justify-center rounded-2xl bg-stone-100 text-stone-400 ring-1 ring-stone-200 dark:bg-stone-700 dark:text-stone-500 dark:ring-stone-600" aria-hidden="true">
                                <svg class="size-14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="1" />
                                    <rect x="14" y="3" width="7" height="7" rx="1" />
                                    <rect x="3" y="14" width="7" height="7" rx="1" />
                                    <path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z" />
                                </svg>
                            </div>
                        @endif
                    </div>

                    <label class="cursor-pointer rounded-lg bg-stone-100 px-3 py-1.5 text-xs font-medium text-stone-700 hover:bg-stone-200 dark:bg-stone-700 dark:text-stone-200 dark:hover:bg-stone-600">
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
                        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-center dark:border-red-900 dark:bg-red-900/30">
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
                        PNG, JPG o WebP. Máx. 2 MB.
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
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                Guardar cambios
            </button>
        </div>
    </form>
</div>
