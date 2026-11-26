@php($enLivewire = $enLivewire ?? false)

@if ($paginator->hasPages())
    <nav data-reporte-paginacion class="flex items-center justify-center gap-2" aria-label="Paginación">
        @if ($paginator->onFirstPage())
            <span class="cursor-not-allowed rounded-lg border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-400 dark:border-stone-700 dark:text-stone-600">Anterior</span>
        @elseif ($enLivewire)
            <button type="button"
                    wire:click="gotoPage({{ $paginator->currentPage() - 1 }})"
                    data-preservar-scroll
                    rel="prev"
                    class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700">Anterior</button>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
               class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700">Anterior</a>
        @endif

        <span class="text-xs font-medium text-stone-600 tabular-nums dark:text-stone-300">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        </span>

        @if ($paginator->hasMorePages())
            @if ($enLivewire)
                <button type="button"
                        wire:click="gotoPage({{ $paginator->currentPage() + 1 }})"
                        data-preservar-scroll
                        rel="next"
                        class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700">Siguiente</button>
            @else
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-300 dark:hover:bg-stone-700">Siguiente</a>
            @endif
        @else
            <span class="cursor-not-allowed rounded-lg border border-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-400 dark:border-stone-700 dark:text-stone-600">Siguiente</span>
        @endif
    </nav>
@endif
