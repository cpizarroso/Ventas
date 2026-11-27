<div
    id="popover-producto-{{ $producto->id }}"
    role="tooltip"
    x-show="popoverAbierto"
    x-cloak
    class="pointer-events-none absolute left-1/2 top-full z-40 mt-2 w-64 max-w-[calc(100vw-2rem)] -translate-x-1/2 rounded-xl border border-stone-200 bg-white p-3 shadow-xl ring-1 ring-black/5 dark:border-stone-700 dark:bg-stone-800"
>
    <p class="mb-2 text-[10px] font-bold uppercase tracking-wide text-stone-400 dark:text-stone-500">
        Detalle del producto
    </p>

    @include('livewire.ventas._ficha-producto', ['producto' => $producto])
</div>
