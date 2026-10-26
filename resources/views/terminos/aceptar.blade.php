<x-layouts.legal
    :negocio="$negocio"
    :nombre-comercial="$nombreComercial"
    title="Aceptar términos"
>
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-400">Antes de continuar</p>
            <h1 class="mt-2 text-2xl font-semibold text-stone-900 sm:text-3xl dark:text-stone-100">
                Acepta los términos y la política de privacidad
            </h1>
            <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
                Para usar JIBAS debes aceptar la versión vigente. La aceptación quedará registrada con la fecha, tu usuario
                y la versión del documento.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/30 dark:text-red-300" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="max-h-[55vh] overflow-y-auto rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-7 dark:border-stone-700 dark:bg-stone-800">
            @include('terminos._documento')
        </div>

        <form method="POST" action="{{ route('terminos.store') }}" class="mt-5 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm sm:p-6 dark:border-stone-700 dark:bg-stone-800">
            @csrf

            <label for="aceptar" class="flex items-start gap-3 text-sm text-stone-700 dark:text-stone-300">
                <input id="aceptar"
                       name="aceptar"
                       type="checkbox"
                       value="1"
                       @checked(old('aceptar'))
                       required
                       aria-describedby="aceptar-ayuda"
                       class="mt-0.5 size-4 shrink-0 rounded border-stone-300 text-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-900">
                <span>
                    He leído y acepto los Términos y Condiciones y la Política de Privacidad, versión {{ $version }},
                    vigente desde el {{ \Illuminate\Support\Carbon::parse($vigenteDesde)->format('d/m/Y') }}, en mi nombre
                    y en representación del negocio.
                </span>
            </label>
            <p id="aceptar-ayuda" class="mt-2 pl-7 text-xs text-stone-500 dark:text-stone-400">
                Puedes consultar el documento completo en
                <a href="{{ route('terminos.index') }}" class="underline decoration-stone-300 underline-offset-4 hover:text-stone-800 dark:decoration-stone-600 dark:hover:text-stone-200">esta página</a>.
            </p>

            <button type="submit"
                    class="mt-5 w-full rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 sm:w-auto dark:focus:ring-offset-stone-800">
                Aceptar y continuar
            </button>
        </form>
    </div>
</x-layouts.legal>
