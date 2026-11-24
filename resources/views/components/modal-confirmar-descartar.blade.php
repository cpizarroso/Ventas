@props([
    'id' => 'modal-confirmar-descartar',
    'abierto' => false,
    'mantener' => 'mantenerCambios',
    'descartar' => 'descartarCambios',
])

<div @class([
    'fixed inset-0 z-[70] flex items-center justify-center bg-stone-900/70 p-4 backdrop-blur-sm',
    'hidden' => ! $abierto,
])
     role="alertdialog"
     aria-modal="true"
     aria-labelledby="{{ $id }}-titulo"
     aria-describedby="{{ $id }}-descripcion"
     @if ($abierto) wire:keydown.escape.window="{{ $mantener }}" @endif
     @if (! $abierto) aria-hidden="true" @endif>
    <div class="absolute inset-0" wire:click="{{ $mantener }}" aria-hidden="true"></div>

    <div class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl dark:bg-stone-800 sm:p-6">
        <div class="flex items-start gap-3">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 id="{{ $id }}-titulo" class="text-base font-semibold text-stone-900 dark:text-stone-100">
                    ¿Descartar los cambios?
                </h3>
                <p id="{{ $id }}-descripcion" class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                    Los datos que modificaste se perderán y no se guardarán.
                </p>
            </div>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button"
                    wire:click="{{ $mantener }}"
                    class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                Seguir editando
            </button>
            <button type="button"
                    wire:click="{{ $descartar }}"
                    class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                Descartar cambios
            </button>
        </div>
    </div>
</div>
