<x-layouts.legal
    :negocio="$negocio"
    :nombre-comercial="$nombreComercial"
    title="Aceptar términos"
>
    <div class="mx-auto max-w-3xl space-y-4">
        @if (session('aviso_terminos'))
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-900/20 dark:text-amber-200" role="alert">
                {{ session('aviso_terminos') }}
            </div>
        @endif

        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
            <h1 class="text-xl font-semibold text-stone-900 dark:text-stone-100 sm:text-2xl">
                Aceptación de términos y condiciones
            </h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">
                Para continuar necesitas revisar y aceptar la versión {{ config('jibas.terminos.version') }} de los Términos y Condiciones y la Política de Privacidad, vigente desde el
                {{ \Illuminate\Support\Carbon::parse(config('jibas.terminos.vigente_desde'))->format('d/m/Y') }}.
            </p>
            <p class="mt-3 text-xs text-stone-500 dark:text-stone-400">
                El documento completo se muestra en la ventana abierta. Usa los botones «Aceptar y continuar» o «Rechazar y salir» al pie del modal o al final del texto.
            </p>
        </div>
    </div>

    <x-terminos-modal id="modal-aceptar-terminos"
                      :obligatorio="true"
                      :auto-abrir="true" />
</x-layouts.legal>
