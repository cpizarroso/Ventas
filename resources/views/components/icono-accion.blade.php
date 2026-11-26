@props([
    'accion',
    'titulo',
    'click',
    'desactivado' => false,
])

@php
    $clasesBase = 'rounded-lg p-1.5 shadow-sm ring-1 transition-all hover:scale-105 active:scale-95 focus-visible:ring-2 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-stone-800';

    $clasesAccion = match ($accion) {
        'editar' => 'bg-sky-50 text-sky-700 ring-sky-200 hover:bg-sky-100 focus-visible:ring-sky-500 dark:bg-sky-900/30 dark:text-sky-300 dark:ring-sky-800 dark:hover:bg-sky-900/50',
        'baja' => 'bg-red-50 text-red-700 ring-red-200 hover:bg-red-100 focus-visible:ring-red-500 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800 dark:hover:bg-red-900/50',
        'reactivar' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 hover:bg-emerald-100 focus-visible:ring-emerald-500 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/50',
        'restablecer' => 'bg-amber-50 text-amber-700 ring-amber-200 hover:bg-amber-100 focus-visible:ring-amber-500 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800 dark:hover:bg-amber-900/50',
        default => 'bg-stone-50 text-stone-700 ring-stone-200 hover:bg-stone-100 focus-visible:ring-stone-500 dark:bg-stone-700 dark:text-stone-200 dark:ring-stone-600 dark:hover:bg-stone-600',
    };
@endphp

<button type="button"
        wire:click="{{ $click }}"
        data-preservar-scroll
        @if ($desactivado) disabled @endif
        title="{{ $titulo }}"
        aria-label="{{ $titulo }}"
        class="{{ $clasesBase }} {{ $clasesAccion }}">
    @switch($accion)
        @case('editar')
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
            </svg>
            @break
        @case('baja')
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
            @break
        @case('reactivar')
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            @break
        @case('restablecer')
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6.5 10V7a3.5 3.5 0 0 1 7 0v3" />
                <path d="M5 10h7a1.5 1.5 0 0 1 1.5 1.5v7A1.5 1.5 0 0 1 12 20H5a1.5 1.5 0 0 1-1.5-1.5v-7A1.5 1.5 0 0 1 5 10Z" />
                <path d="M15 14h5m0 0-2-2m2 2-2 2" />
            </svg>
            @break
        @default
            <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
            </svg>
    @endswitch
</button>
