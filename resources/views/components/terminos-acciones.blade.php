@props([
    'accionAceptar',
    'accionRechazar',
    'rechazarEtiqueta' => 'Rechazar y salir',
])

<div {{ $attributes->class(['flex flex-wrap justify-end gap-2']) }}>
    <form method="POST" action="{{ $accionRechazar }}">
        @csrf
        <button type="submit"
                class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:ring-offset-2 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-800">
            {{ $rechazarEtiqueta }}
        </button>
    </form>

    <form method="POST" action="{{ $accionAceptar }}">
        @csrf
        <button type="submit"
                class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
            Aceptar y continuar
        </button>
    </form>
</div>
