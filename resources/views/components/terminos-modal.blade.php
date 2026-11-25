@props([
    'id' => 'modal-terminos',
    'obligatorio' => false,
    'autoAbrir' => false,
    'accionAceptar' => null,
    'accionRechazar' => null,
])

@php
    $titular = config('jibas.legal.titular');
    $contacto = config('jibas.legal.contacto');
    $ciudad = config('jibas.legal.ciudad');
    $pais = config('jibas.legal.pais');
    $version = config('jibas.terminos.version');
    $vigenteDesde = config('jibas.terminos.vigente_desde');
    $fechaVigencia = \Illuminate\Support\Carbon::parse($vigenteDesde)->format('d/m/Y');
    $urlAceptar = $accionAceptar ?? route('terminos.aceptar.store');
    $urlRechazar = $accionRechazar ?? route('terminos.rechazar');
@endphp

<dialog
    id="{{ $id }}"
    aria-labelledby="{{ $id }}-titulo"
    data-obligatorio="{{ $obligatorio ? '1' : '0' }}"
    @if ($autoAbrir) data-auto-abrir-terminos @endif
    class="relative m-auto w-[calc(100%-2rem)] max-w-3xl overflow-hidden rounded-2xl border border-stone-200 bg-white p-0 text-stone-900 shadow-xl dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"
>
    <button
        type="button"
        @if ($obligatorio)
            data-terminos-advertir
        @else
            data-cerrar-terminos
        @endif
        title="{{ $obligatorio ? 'Ver advertencia antes de cerrar' : 'Cerrar términos y condiciones' }}"
        aria-label="{{ $obligatorio ? 'Ver advertencia antes de cerrar' : 'Cerrar términos y condiciones' }}"
        class="absolute right-3 top-3 z-20 flex items-center gap-1.5 rounded-full border border-stone-300 bg-white/95 px-3 py-2 text-xs font-semibold text-stone-700 shadow-md backdrop-blur hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-800/95 dark:text-stone-200 dark:hover:bg-stone-700 dark:hover:text-white"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12" />
        </svg>
        Cerrar
    </button>

    <div class="flex max-h-[90vh] flex-col">
        <header class="flex items-start border-b border-stone-200 px-5 py-4 pr-28 dark:border-stone-700 sm:px-6 sm:pr-32">
            <div>
                <h2 id="{{ $id }}-titulo" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                    Términos y Condiciones
                </h2>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    Versión {{ $version }} · Vigente desde el {{ $fechaVigencia }}
                </p>
            </div>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            @if ($obligatorio)
                <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
                    <p class="font-semibold">Revisa el documento completo antes de continuar.</p>
                    <p class="mt-1">
                        Si no aceptas los términos vigentes, se cerrará tu sesión. Puedes usar los botones al pie del modal o al final del documento.
                    </p>
                </div>
            @endif

            @include('terminos._documento')

            @if ($obligatorio)
                <div class="mt-8 border-t border-stone-200 pt-5 dark:border-stone-700">
                    <p class="mb-3 text-xs text-stone-500 dark:text-stone-400">
                        Al hacer clic en «Aceptar y continuar» confirmas que leíste y aceptas la versión {{ $version }} de estos términos.
                    </p>
                    <x-terminos-acciones :accion-aceptar="$urlAceptar" :accion-rechazar="$urlRechazar" />
                </div>
            @endif
        </div>

        <footer class="flex justify-end border-t border-stone-200 px-5 py-4 dark:border-stone-700 sm:px-6">
            @if ($obligatorio)
                <x-terminos-acciones :accion-aceptar="$urlAceptar" :accion-rechazar="$urlRechazar" />
            @else
                <button
                    type="button"
                    data-cerrar-terminos
                    class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800"
                >
                    Cerrar
                </button>
            @endif
        </footer>

        @if ($obligatorio)
            <div data-terminos-confirmacion
                 role="alertdialog"
                 aria-modal="true"
                 aria-labelledby="{{ $id }}-confirmacion-titulo"
                 aria-describedby="{{ $id }}-confirmacion-texto"
                 class="absolute inset-0 z-10 hidden items-center justify-center bg-stone-900/50 p-4">
                <div class="w-full max-w-sm rounded-2xl border border-stone-200 bg-white p-5 shadow-2xl dark:border-stone-700 dark:bg-stone-800">
                    <h3 id="{{ $id }}-confirmacion-titulo" class="text-base font-semibold text-stone-900 dark:text-stone-100">¿Salir sin aceptar?</h3>
                    <p id="{{ $id }}-confirmacion-texto" class="mt-2 text-sm text-stone-600 dark:text-stone-300">
                        Si no aceptas los términos vigentes, se cerrará tu sesión y tendrás que iniciar sesión de nuevo para continuar.
                    </p>
                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button type="button"
                                data-terminos-volver
                                class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                            Volver a los términos
                        </button>
                        <form method="POST" action="{{ $urlRechazar }}">
                            @csrf
                            <button type="submit"
                                    class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</dialog>

@once
    <script>
        (function () {
            function abrir(dialog) {
                if (dialog && typeof dialog.showModal === 'function' && ! dialog.open) {
                    dialog.showModal();
                }
            }

            function esObligatorio(dialog) {
                return dialog instanceof HTMLDialogElement && dialog.dataset.obligatorio === '1';
            }

            function mostrarAdvertencia(dialog) {
                if (! esObligatorio(dialog)) {
                    return;
                }

                const panel = dialog.querySelector('[data-terminos-confirmacion]');

                if (! panel) {
                    return;
                }

                panel.classList.remove('hidden');
                panel.classList.add('flex');
                panel.querySelector('[data-terminos-volver]')?.focus();
            }

            function ocultarAdvertencia(dialog) {
                if (! dialog) {
                    return;
                }

                const panel = dialog.querySelector('[data-terminos-confirmacion]');

                if (panel) {
                    panel.classList.add('hidden');
                    panel.classList.remove('flex');
                }

                dialog.querySelector('[data-terminos-advertir]')?.focus();
            }

            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-auto-abrir-terminos]').forEach(abrir);
            });

            document.addEventListener('click', function (event) {
                const objetivo = event.target instanceof Element ? event.target : null;

                if (! objetivo) {
                    return;
                }

                const opener = objetivo.closest('[data-abrir-terminos]');

                if (opener) {
                    event.preventDefault();
                    abrir(document.querySelector(opener.getAttribute('data-abrir-terminos')));

                    return;
                }

                const volver = objetivo.closest('[data-terminos-volver]');

                if (volver) {
                    event.preventDefault();
                    ocultarAdvertencia(volver.closest('dialog'));

                    return;
                }

                const advertir = objetivo.closest('[data-terminos-advertir]');

                if (advertir) {
                    event.preventDefault();
                    mostrarAdvertencia(advertir.closest('dialog'));

                    return;
                }

                const closer = objetivo.closest('[data-cerrar-terminos]');

                if (closer) {
                    event.preventDefault();

                    const dialog = closer.closest('dialog');

                    if (esObligatorio(dialog)) {
                        mostrarAdvertencia(dialog);
                    } else {
                        dialog?.close();
                    }

                    return;
                }

                if (event.target instanceof HTMLDialogElement && event.target.open) {
                    event.preventDefault();

                    if (esObligatorio(event.target)) {
                        mostrarAdvertencia(event.target);
                    } else {
                        event.target.close();
                    }
                }
            });

            document.addEventListener('cancel', function (event) {
                if (esObligatorio(event.target)) {
                    event.preventDefault();
                    mostrarAdvertencia(event.target);
                }
            }, true);
        })();
    </script>
@endonce
