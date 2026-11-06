<div wire:key="galeria-predeterminada-{{ $prefijo }}"
     class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4 backdrop-blur-sm"
     role="dialog"
     aria-modal="true"
     aria-labelledby="{{ $tituloId }}"
     wire:keydown.escape.window="{{ $accionCerrar }}">
    <div class="absolute inset-0" wire:click="{{ $accionCerrar }}" aria-hidden="true"></div>

    <div class="relative flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-stone-800">
        <div class="flex items-start justify-between gap-3 border-b border-stone-200 px-5 py-4 dark:border-stone-700">
            <div>
                <h3 id="{{ $tituloId }}" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                    Elegir imagen predeterminada
                </h3>
                <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                    {{ count($imagenes) }} imágenes disponibles. Se guardará una copia optimizada en el ítem.
                </p>
            </div>
            <button type="button"
                    wire:click="{{ $accionCerrar }}"
                    class="rounded-lg p-1.5 text-stone-500 hover:bg-stone-100 hover:text-stone-800 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white"
                    aria-label="Cerrar galería">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto px-5 py-4">
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8">
                @foreach ($imagenes as $imagen)
                    <button
                        type="button"
                        wire:key="{{ $prefijo }}-predeterminada-{{ $loop->index }}"
                        wire:click="{{ $accionSeleccionar }}({{ $loop->index }})"
                        @class([
                            'relative aspect-square overflow-hidden rounded-lg border-2 bg-stone-100 transition focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-stone-700',
                            'border-amber-500 ring-2 ring-amber-400/60' => $seleccionada === $imagen['archivo'],
                            'border-transparent hover:border-amber-300' => $seleccionada !== $imagen['archivo'],
                        ])
                        title="{{ $imagen['archivo'] }}"
                        aria-label="Usar imagen predeterminada {{ $imagen['archivo'] }}"
                        aria-pressed="{{ $seleccionada === $imagen['archivo'] ? 'true' : 'false' }}"
                    >
                        <img src="{{ $imagen['url'] }}" alt="{{ $imagen['archivo'] }}" class="size-full object-cover" loading="lazy">
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>
