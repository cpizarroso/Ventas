@props([
    'campo',
    'etiqueta',
    'orden',
    'direccion' => 'asc',
    'accion',
    'alinear' => 'left',
    'deshabilitado' => false,
])

@php
    $activo = $orden === $campo;
    $ascendente = $activo && $direccion === 'asc';
    $descendente = $activo && $direccion === 'desc';
@endphp

<th scope="col"
    aria-sort="{{ $activo ? ($ascendente ? 'ascending' : 'descending') : 'none' }}"
    class="px-3 py-2.5 {{ $alinear === 'right' ? 'text-right' : 'text-left' }}">
    <button type="button"
            wire:click="{{ $accion }}('{{ $campo }}')"
            data-preservar-scroll
            @if ($deshabilitado) disabled @endif
            title="Ordenar por {{ $etiqueta }}"
            aria-label="Ordenar por {{ $etiqueta }}{{ $ascendente ? ', ascendente' : ($descendente ? ', descendente' : '') }}"
            @class([
                'group inline-flex w-full items-center gap-1.5 rounded-md transition-colors',
                'justify-end' => $alinear === 'right',
                'justify-start' => $alinear !== 'right',
                'text-amber-700 dark:text-amber-300' => $activo,
                'text-stone-500 hover:text-stone-900 dark:text-stone-400 dark:hover:text-white' => ! $activo,
                'disabled:cursor-not-allowed disabled:opacity-50' => $deshabilitado,
            ])>
        <span>{{ $etiqueta }}</span>
        @if ($ascendente)
            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 14 6-6 6 6" />
            </svg>
        @elseif ($descendente)
            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m6 10 6 6 6-6" />
            </svg>
        @else
            <svg class="size-3.5 shrink-0 opacity-60 transition-opacity group-hover:opacity-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m7 10 5-5 5 5" />
                <path d="m7 14 5 5 5-5" />
            </svg>
        @endif
    </button>
</th>
