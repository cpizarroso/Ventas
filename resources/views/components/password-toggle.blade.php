@props(['target'])

<button type="button"
        data-toggle-password="{{ $target }}"
        aria-controls="{{ $target }}"
        aria-label="Mostrar contraseña"
        aria-pressed="false"
        {{ $attributes->class(['absolute inset-y-0 right-0 flex items-center px-3 text-stone-500 transition hover:text-stone-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-stone-400 dark:hover:text-stone-100']) }}>
    <svg data-password-icon-show class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
        <circle cx="12" cy="12" r="3" />
    </svg>
    <svg data-password-icon-hide class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M3 3l18 18" />
        <path d="M10.58 10.58a3 3 0 0 0 4.24 4.24" />
        <path d="M9.88 5.09A10.94 10.94 0 0 1 12 5c6.5 0 10 7 10 7a18.45 18.45 0 0 1-2.06 3.19" />
        <path d="M6.61 6.61A18.45 18.45 0 0 0 2 12s3.5 7 10 7a10.94 10.94 0 0 0 5.39-1.39" />
    </svg>
</button>
