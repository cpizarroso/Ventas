@props([
    'id',
    'titulo',
    'descripcion' => null,
    'cerrar' => 'cerrarModal',
    'ancho' => 'max-w-3xl',
    'abierto' => false,
])

<div @class([
    'fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm',
    'hidden' => ! $abierto,
])
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $id }}-titulo"
     @if ($abierto) wire:keydown.escape.window="{{ $cerrar }}" @endif
     @if (! $abierto) aria-hidden="true" @endif>
    <div class="absolute inset-0" wire:click="{{ $cerrar }}" aria-hidden="true"></div>

    <div class="relative flex max-h-[88vh] w-full {{ $ancho }} flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-stone-800">
        <header class="flex items-start justify-between gap-4 border-b border-stone-200 px-5 py-4 dark:border-stone-700 sm:px-6">
            <div>
                <h3 id="{{ $id }}-titulo" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                    {{ $titulo }}
                </h3>
                @if ($descripcion !== null)
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $descripcion }}</p>
                @endif
            </div>
            <button type="button"
                    wire:click="{{ $cerrar }}"
                    class="rounded-lg p-1.5 text-stone-500 hover:bg-stone-100 hover:text-stone-800 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
                    aria-label="Cerrar ventana">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            {{ $slot }}
        </div>
    </div>
</div>
