@props([
    'id' => 'modal-terminos',
])

@php
    $titular = config('jibas.legal.titular');
    $contacto = config('jibas.legal.contacto');
    $ciudad = config('jibas.legal.ciudad');
    $pais = config('jibas.legal.pais');
    $version = config('jibas.terminos.version');
    $vigenteDesde = config('jibas.terminos.vigente_desde');
    $fechaVigencia = \Illuminate\Support\Carbon::parse($vigenteDesde)->format('d/m/Y');
@endphp

<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-titulo"
    class="m-auto w-[calc(100%-2rem)] max-w-3xl overflow-hidden rounded-2xl border border-stone-200 bg-white p-0 text-stone-900 shadow-xl dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
>
    <div class="flex max-h-[85vh] flex-col">
        <header class="flex items-start justify-between gap-4 border-b border-stone-200 px-5 py-4 dark:border-stone-700 sm:px-6">
            <div>
                <h2 id="{{ $id }}-titulo" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                    Términos y Condiciones
                </h2>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    Versión {{ $version }} · Vigente desde el {{ $fechaVigencia }}
                </p>
            </div>
            <button
                type="button"
                data-cerrar-terminos
                class="rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
                aria-label="Cerrar términos y condiciones"
            >
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
            </button>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            @include('terminos._documento')
        </div>

        <footer class="flex justify-end border-t border-stone-200 px-5 py-4 dark:border-stone-700 sm:px-6">
            <button
                type="button"
                data-cerrar-terminos
                class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
            >
                Cerrar
            </button>
        </footer>
    </div>
</dialog>

@once
    <script>
        document.addEventListener('click', function (event) {
            const opener = event.target.closest('[data-abrir-terminos]');

            if (opener) {
                event.preventDefault();

                const modal = document.querySelector(opener.getAttribute('data-abrir-terminos'));

                if (modal && typeof modal.showModal === 'function') {
                    modal.showModal();
                }

                return;
            }

            const closer = event.target.closest('[data-cerrar-terminos]');

            if (closer) {
                event.preventDefault();
                closer.closest('dialog')?.close();

                return;
            }

            if (event.target instanceof HTMLDialogElement && event.target.open) {
                event.target.close();
            }
        });
    </script>
@endonce
