<x-layouts.app>
    <div class="mb-6">
        <a href="{{ route('ventas.index') }}"
           class="inline-flex items-center gap-1.5 text-sm font-medium text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5m0 0 7 7m-7-7 7-7" />
            </svg>
            Volver a ventas
        </a>
        <div class="mt-2">
            <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">Abrir caja</h1>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                Registra el monto inicial de efectivo para comenzar a cobrar.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('cajas.store') }}"
              class="space-y-5 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
            @csrf

            @if (old('origen', $origen) === 'ventas')
                <input type="hidden" name="origen" value="ventas">
            @endif

            <div>
                <label for="monto_inicial" class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">
                    Monto inicial de efectivo
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-stone-500 dark:text-stone-400">Bs</span>
                    <input type="number" id="monto_inicial" name="monto_inicial" step="0.01" min="0" max="9999999"
                           value="{{ old('monto_inicial', '0.00') }}" required autofocus
                           class="w-full rounded-xl border border-stone-300 bg-white py-2.5 pl-10 pr-3 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100">
                </div>
                @error('monto_inicial')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="observaciones_apertura" class="mb-1 block text-sm font-semibold text-stone-700 dark:text-stone-200">
                    Observaciones <span class="font-normal text-stone-400 dark:text-stone-500">(opcional)</span>
                </label>
                <textarea id="observaciones_apertura" name="observaciones_apertura" rows="3" maxlength="255"
                          class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2.5 text-sm text-stone-800 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
                          placeholder="Ej. Sencillo inicial, observaciones del turno…">{{ old('observaciones_apertura') }}</textarea>
                @error('observaciones_apertura')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                Mientras la caja esté abierta podrás cobrar ventas. Al cerrarla se calcularán los totales del turno.
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ old('origen', $origen) === 'ventas' ? route('ventas.index') : route('cajas.index') }}"
                   class="rounded-xl border border-stone-300 px-4 py-2.5 text-center text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                    Abrir caja
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>