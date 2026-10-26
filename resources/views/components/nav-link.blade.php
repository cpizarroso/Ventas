@props([
    'href',
    'active' => false,
])

<a href="{{ $href }}"
   @if ($active) aria-current="page" @endif
   {{ $attributes->class([
       'rounded-lg px-3 py-2 text-sm font-medium',
       'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200' => $active,
       'text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white' => ! $active,
   ]) }}>
    {{ $slot }}
</a>
