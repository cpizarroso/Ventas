<div
    x-data="{
        visible: false,
        message: '',
        timer: null,
        showToast(detail) {
            this.message = detail?.message ?? '';
            this.visible = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.visible = false, 4000);
        },
    }"
    x-on:toast.window="showToast($event.detail)"
    x-cloak
    x-show="visible"
    x-transition.opacity.duration.200ms
    class="pointer-events-none fixed inset-x-4 top-20 z-50 flex justify-end sm:left-auto sm:right-6 sm:w-full sm:max-w-sm"
    role="status"
    aria-live="polite"
>
    <div class="pointer-events-auto flex w-full items-start gap-3 rounded-2xl border border-emerald-200 bg-white p-4 shadow-lg ring-1 ring-black/5 dark:border-emerald-800 dark:bg-stone-800">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300" aria-hidden="true">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m5 12 4 4L19 6" />
            </svg>
        </span>

        <p class="min-w-0 flex-1 pt-1.5 text-sm font-medium text-stone-800 dark:text-stone-100" x-text="message"></p>

        <button
            type="button"
            x-on:click="visible = false; clearTimeout(timer)"
            class="flex size-8 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:text-stone-500 dark:hover:bg-stone-700 dark:hover:text-stone-200"
            aria-label="Cerrar notificación"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
