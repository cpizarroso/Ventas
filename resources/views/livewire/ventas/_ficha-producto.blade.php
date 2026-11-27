@php
    $sinStock = $producto->sinStock();
    $stockBajo = $producto->stockBajo();
@endphp

<div class="flex items-start gap-3">
    <div class="size-14 shrink-0 overflow-hidden rounded-xl bg-stone-100 ring-1 ring-stone-200 dark:bg-stone-700 dark:ring-stone-600">
        @if ($producto->imagenUrl())
            <img src="{{ $producto->imagenUrl() }}" alt="" class="size-full object-contain" loading="lazy">
        @else
            <div class="flex size-full items-center justify-center text-stone-400 dark:text-stone-500" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </div>
        @endif
    </div>

    <div class="min-w-0">
        <p class="break-words text-sm font-bold leading-tight text-stone-900 dark:text-stone-50">
            {{ $producto->nombre }}
        </p>

        @if ($producto->categoria?->nombre)
            <p class="mt-0.5 truncate text-[10px] font-semibold uppercase tracking-wide text-stone-400 dark:text-stone-500">
                {{ $producto->categoria->nombre }}
            </p>
        @endif

        <p class="mt-1 text-sm font-bold text-amber-700 tabular-nums dark:text-amber-300">
            Bs {{ number_format((float) $producto->precio, 2) }}
        </p>

        <p @class([
            'mt-1 text-[11px] font-semibold tabular-nums',
            'text-emerald-600 dark:text-emerald-400' => $producto->stock === null || (! $sinStock && ! $stockBajo),
            'text-amber-700 dark:text-amber-300' => $stockBajo,
            'text-red-600 dark:text-red-400' => $sinStock,
        ])>
            @if ($producto->stock === null)
                Stock: ilimitado
            @else
                Stock: {{ $producto->stock }}
            @endif
        </p>
    </div>
</div>
