@props(['titulo' => 'módulo'])

<div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-700 dark:bg-stone-800"
     aria-label="Cargando {{ $titulo }}">
    <div class="flex items-center gap-2">
        <span class="size-2 animate-pulse rounded-full bg-amber-400" aria-hidden="true"></span>
        <p class="text-sm font-semibold text-stone-600 dark:text-stone-300">Cargando {{ $titulo }}…</p>
    </div>
    <div class="mt-4 space-y-3" aria-hidden="true">
        <div class="h-3 w-1/3 animate-pulse rounded bg-stone-200 dark:bg-stone-700"></div>
        <div class="h-10 animate-pulse rounded-xl bg-stone-100 dark:bg-stone-700/60"></div>
        <div class="h-10 animate-pulse rounded-xl bg-stone-100 dark:bg-stone-700/60"></div>
    </div>
</div>
