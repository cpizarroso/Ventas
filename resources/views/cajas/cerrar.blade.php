<x-layouts.app>
    <div class="mb-6">
        <a href="{{ route('cajas.show', $caja) }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
            </svg>
            Volver a la caja
        </a>
        <div class="mt-2">
            <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Cerrar caja #{{ $caja->id }}</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                Revisa los totales e ingresa el efectivo contado para cerrar el turno.
            </p>
        </div>
    </div>

    <div class="mx-auto grid max-w-3xl gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="space-y-3">
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Monto inicial</p>
                <p class="mt-1 text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">Bs {{ number_format((float) $caja->monto_inicial, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ventas</p>
                <p class="mt-1 text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">{{ $resumen['cantidad'] }}</p>
            </div>
            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800">
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Total vendido</p>
                <p class="mt-1 text-lg font-bold text-stone-900 tabular-nums dark:text-stone-100">Bs {{ number_format($resumen['total'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Efectivo cobrado</p>
                <p class="mt-1 text-lg font-bold text-emerald-900 tabular-nums dark:text-emerald-200">Bs {{ number_format($resumen['efectivo'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-400">Cobrado por QR</p>
                <p class="mt-1 text-lg font-bold text-sky-900 tabular-nums dark:text-sky-200">Bs {{ number_format($resumen['qr'], 2) }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-800 dark:text-amber-300">Efectivo esperado</p>
                <p class="mt-1 text-lg font-bold text-amber-900 tabular-nums dark:text-amber-200">Bs {{ number_format($montoEsperado, 2) }}</p>
                <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">Monto inicial + efectivo cobrado.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('cajas.cerrar.store', $caja) }}"
              class="space-y-5 self-start rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
            @csrf

            <div>
                <label for="monto_cierre" class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">
                    Efectivo contado
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-stone-500 dark:text-stone-400">Bs</span>
                    <input type="number" id="monto_cierre" name="monto_cierre" step="0.01" min="0" max="9999999"
                           value="{{ old('monto_cierre', number_format($montoEsperado, 2, '.', '')) }}" required autofocus
                           class="w-full rounded-xl border border-stone-300 bg-white py-2.5 pl-10 pr-3 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100">
                </div>
                @error('monto_cierre')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="observaciones_cierre" class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">
                    Observaciones <span class="font-normal text-stone-400 dark:text-stone-500">(opcional)</span>
                </label>
                <textarea id="observaciones_cierre" name="observaciones_cierre" rows="3" maxlength="255"
                          class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
                          placeholder="Ej. Diferencias, novedades del turno…">{{ old('observaciones_cierre') }}</textarea>
                @error('observaciones_cierre')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl bg-stone-100 px-4 py-3 text-xs text-stone-600 dark:bg-stone-900/60 dark:text-stone-300">
                Al cerrar la caja no podrás registrar más ventas hasta abrir una nueva.
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('cajas.show', $caja) }}"
                   class="rounded-xl border border-stone-300 px-4 py-2.5 text-center text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-bold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                    Cerrar caja
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>