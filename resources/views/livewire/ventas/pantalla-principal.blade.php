<div
    x-data="{
        carritoAbierto: false,
        qrAmpliado: false,
        cobrandoConEnter: false,

        async cobrarConEnter(event) {
            if (event.target instanceof Element && event.target.closest('button, a, input[type=checkbox], input[type=radio]')) {
                return;
            }

            const modal = document.querySelector('[aria-labelledby=titulo-pago]');

            if (! modal || (event.target !== document.body && ! modal.contains(event.target))) {
                return;
            }

            event.preventDefault();

            if (this.cobrandoConEnter) {
                return;
            }

            this.cobrandoConEnter = true;

            try {
                for (const id of ['montoRecibido', 'glosa', 'cliente']) {
                    const campo = document.getElementById(id);

                    if (campo) {
                        await this.$wire.set(id, campo.value);
                    }
                }

                await this.$wire.call('cobrar');
            } finally {
                this.cobrandoConEnter = false;
            }
        },
    }"
    x-on:keydown.escape.window="carritoAbierto = false; qrAmpliado = false"
>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    @include('livewire.ventas._venta-registrada')

    @if ($negocio === null)
        <div class="mx-auto max-w-lg rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm dark:border-stone-700 dark:bg-stone-800">
            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-stone-100 text-stone-400 dark:bg-stone-700 dark:text-stone-500" aria-hidden="true">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l1.5-5h15L21 9" />
                    <path d="M5 11v8a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-8" />
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Tu usuario no tiene un negocio asignado</h2>
            <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                Contacta al superadministrador para que te asigne a un negocio y puedas empezar a vender.
            </p>
        </div>
    @else
        <div class="mb-4">
            <x-resumen-caja :caja="$cajaAbierta" />
        </div>

        <div class="lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start lg:gap-6">
            <livewire:ventas.catalogo
                :cantidades-productos="$cantidadesPorProducto"
                :cantidades-promociones="$cantidadesPromocion"
            />

            <aside class="mt-6 hidden lg:mt-0 lg:block" aria-label="Detalle de la venta">
                <div class="sticky top-6 flex max-h-[calc(100dvh-10rem)] flex-col overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-800">
                    @include('livewire.ventas._detalle-venta', ['prefijo' => 'escritorio', 'mostrarCerrar' => false])
                </div>
            </aside>
        </div>

        @include('livewire.ventas._barra-carrito-movil')

        <div x-cloak x-show="carritoAbierto" class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Detalle de la venta">
            <div
                x-show="carritoAbierto"
                x-transition.opacity
                class="absolute inset-0 bg-stone-900/60 backdrop-blur-sm"
                x-on:click="carritoAbierto = false"
            ></div>

            <div
                x-show="carritoAbierto"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col overflow-hidden bg-white shadow-xl dark:bg-stone-800"
            >
                @include('livewire.ventas._detalle-venta', ['prefijo' => 'movil', 'mostrarCerrar' => true])
            </div>
        </div>
    @endif

    @include('livewire.ventas._modal-pago')

        @include('livewire.ventas._cancelar-venta')
</div>
