@props([
    'caja' => null,
    'variante' => 'banner',
])

@php
    $esPopover = $variante === 'popover';
    $valorCookie = $caja !== null && ! $esPopover && auth()->check()
        ? auth()->id().'-'.$caja->id
        : null;
    $yaCerrado = $valorCookie !== null && request()->cookie('aviso_caja_cerrado') === $valorCookie;
    $sufijoSeguro = request()->secure() ? '; Secure' : '';
@endphp

@if (! $yaCerrado)
@if ($caja !== null)
    <div x-data="{ visible: true }"
         x-show="visible"
         x-transition.opacity.duration.200ms
         @class([
             'flex items-start gap-3 rounded-xl border',
             'border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200' => ! $esPopover,
             'border-stone-200 bg-stone-50 px-3 py-2.5 dark:border-stone-700 dark:bg-stone-900/50' => $esPopover,
         ]) role="status">
        <span @class([
            'flex shrink-0 items-center justify-center rounded-lg',
            'size-9 bg-emerald-100 text-emerald-700 dark:bg-emerald-800/50 dark:text-emerald-300' => ! $esPopover,
            'size-8 bg-emerald-100 text-emerald-700 dark:bg-emerald-800/50 dark:text-emerald-300' => $esPopover,
        ]) aria-hidden="true">
            <svg class="{{ $esPopover ? 'size-4' : 'size-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="6" width="18" height="13" rx="2" />
                <path d="M8 6V4h8v2M3 12h18M9 12v3" />
            </svg>
        </span>

        <div class="min-w-0 flex-1">
            <p @class([
                'font-semibold text-stone-900 dark:text-stone-100' => $esPopover,
                'font-semibold' => ! $esPopover,
            ])>
                Caja abierta
            </p>
            <p class="mt-0.5 text-xs text-stone-600 dark:text-stone-300">
                Desde {{ $caja->fecha_apertura?->format('d/m H:i') ?? '—' }}
                · Monto inicial Bs {{ number_format((float) $caja->monto_inicial, 2) }}
                @if ($caja->user !== null)
                    · Abierta por {{ $caja->user->name }}
                @endif
            </p>
            <div class="mt-2 flex flex-wrap gap-2">
                <a href="{{ route('cajas.cerrar', $caja) }}"
                   class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500">
                    Cerrar caja
                </a>
                <a href="{{ route('cajas.show', $caja) }}"
                   class="rounded-lg border border-stone-300 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                    Ver detalle
                </a>
            </div>
        </div>

        @if (! $esPopover)
            <button type="button"
                    x-on:click="visible = false; document.cookie = 'aviso_caja_cerrado=' + @js($valorCookie) + '; Path=/; Max-Age=2592000; SameSite=Lax' + @js($sufijoSeguro)"
                    class="-mr-1 -mt-1 flex size-7 shrink-0 items-center justify-center rounded-lg text-emerald-700 hover:bg-emerald-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-emerald-300 dark:hover:bg-emerald-800/40"
                    aria-label="Cerrar aviso de caja abierta"
                    title="Cerrar aviso">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 6l12 12M18 6 6 18" />
                </svg>
            </button>
        @endif
    </div>
@else
    <div @class([
        'flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200' => ! $esPopover,
        'flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2.5 text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200' => $esPopover,
    ]) role="alert">
        <span @class([
            'flex shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
            'size-9' => ! $esPopover,
            'size-8' => $esPopover,
        ]) aria-hidden="true">
            <svg class="{{ $esPopover ? 'size-4' : 'size-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v4m0 4h.01" />
            </svg>
        </span>

        <div class="min-w-0 flex-1">
            <p class="font-semibold">No tienes una caja abierta.</p>
            <p class="mt-0.5 text-xs">Abre tu caja para poder cobrar ventas.</p>
            <div class="mt-2">
                <a href="{{ route('cajas.create', ['origen' => 'ventas']) }}"
                   class="inline-flex rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-stone-900 hover:bg-amber-400">
                    Abrir caja
                </a>
            </div>
        </div>
    </div>
@endif
@endif
