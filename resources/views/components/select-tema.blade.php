@props([
    'id' => null,
    'name' => null,
    'label' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Seleccionar…',
])

@php
    $id ??= 'select-'.$name;
    $seleccionado = (string) $selected;
    $opciones = collect($options)
        ->map(fn (array $opcion): array => [
            'valor' => (string) ($opcion['valor'] ?? $opcion['value'] ?? ''),
            'etiqueta' => (string) ($opcion['etiqueta'] ?? $opcion['label'] ?? ''),
        ])
        ->values()
        ->all();
    $etiquetaSeleccionada = collect($opciones)->firstWhere('valor', $seleccionado)['etiqueta'] ?? $placeholder;
@endphp

<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        abierto: false,
        valor: @js($seleccionado),
        etiqueta: @js($etiquetaSeleccionada),
        opciones: @js($opciones),

        elegir(opcion) {
            this.valor = opcion.valor;
            this.etiqueta = opcion.etiqueta;
            this.abierto = false;
            this.$refs.entrada.dispatchEvent(new Event('change', { bubbles: true }));
        },
    }"
    x-on:click.outside="abierto = false"
    x-on:keydown.escape.window="abierto = false"
>
    @if ($label !== '')
        <label id="{{ $id }}-etiqueta" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
            {{ $label }}
        </label>
    @endif

    <input type="hidden" id="{{ $id }}" name="{{ $name }}" x-ref="entrada" x-model="valor" value="{{ $seleccionado }}">

    <button
        type="button"
        x-on:click="abierto = ! abierto"
        x-bind:aria-expanded="abierto ? 'true' : 'false'"
        aria-haspopup="listbox"
        aria-controls="{{ $id }}-panel"
        @if ($label !== '') aria-labelledby="{{ $id }}-etiqueta" @endif
        class="flex w-full items-center justify-between gap-2 rounded-xl border border-stone-300 bg-white px-3 py-2 text-left text-sm text-stone-800 shadow-sm hover:border-amber-400 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/40 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-100"
    >
        <span class="min-w-0 flex-1 truncate" x-text="etiqueta">{{ $etiquetaSeleccionada }}</span>
        <svg class="size-4 shrink-0 text-stone-400 dark:text-stone-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div
        id="{{ $id }}-panel"
        role="listbox"
        x-cloak
        x-show="abierto"
        x-transition:enter="transition duration-150 ease-out"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
        class="absolute z-40 mt-2 max-h-64 w-full min-w-full overflow-y-auto rounded-xl border border-stone-200 bg-white p-1 shadow-xl dark:border-stone-700 dark:bg-stone-800"
    >
        <template x-for="opcion in opciones" :key="opcion.valor">
            <button
                type="button"
                role="option"
                x-on:click="elegir(opcion)"
                x-bind:aria-selected="valor === opcion.valor ? 'true' : 'false'"
                x-bind:class="valor === opcion.valor
                    ? 'bg-amber-500 font-semibold text-stone-900'
                    : 'text-stone-700 hover:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700'"
                class="flex w-full items-center rounded-lg px-3 py-2 text-left text-sm"
                x-text="opcion.etiqueta"
            ></button>
        </template>
    </div>
</div>
