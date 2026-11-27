<div
    x-data="{
        visible: false,
        message: '',
        type: 'success',
        destacado: false,
        secuencia: 0,
        timer: null,
        audioContext: null,
        initAudio() {
            const Contexto = window.AudioContext || window.webkitAudioContext;
            if (!Contexto) return null;

            if (!this.audioContext) {
                this.audioContext = new Contexto();
            }

            return this.audioContext;
        },
        playSound() {
            const contexto = this.initAudio();
            if (!contexto) return;

            const reproducir = () => {
                const ahora = contexto.currentTime;
                const notas = this.type === 'warning'
                    ? [
                        { frecuencia: 520, inicio: 0, duracion: 0.09 },
                        { frecuencia: 390, inicio: 0.11, duracion: 0.13 },
                    ]
                    : [
                        { frecuencia: 880, inicio: 0, duracion: 0.08 },
                        { frecuencia: 1174.66, inicio: 0.09, duracion: 0.14 },
                    ];

                notas.forEach(({ frecuencia, inicio, duracion }) => {
                    const cuando = ahora + inicio;
                    const oscilador = contexto.createOscillator();
                    const ganancia = contexto.createGain();

                    oscilador.type = this.type === 'warning' ? 'triangle' : 'sine';
                    oscilador.frequency.value = frecuencia;
                    ganancia.gain.setValueAtTime(0.0001, cuando);
                    ganancia.gain.exponentialRampToValueAtTime(0.14, cuando + 0.015);
                    ganancia.gain.exponentialRampToValueAtTime(0.0001, cuando + duracion);

                    oscilador.connect(ganancia);
                    ganancia.connect(contexto.destination);
                    oscilador.start(cuando);
                    oscilador.stop(cuando + duracion + 0.03);
                    oscilador.onended = () => {
                        oscilador.disconnect();
                        ganancia.disconnect();
                    };
                });
            };

            if (contexto.state === 'suspended') {
                contexto.resume().then(reproducir).catch(() => {});
            } else {
                reproducir();
            }
        },
        showToast(detail) {
            this.message = detail?.message ?? '';
            this.type = detail?.type ?? 'success';
            this.destacado = detail?.destacado ?? false;
            this.secuencia++;
            this.visible = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.visible = false, 4000);

            if (detail?.sonido !== false) {
                this.playSound();
            }
        },
    }"
    x-on:pointerdown.window.once="initAudio()"
    x-on:keydown.window.once="initAudio()"
    x-on:toast.window="showToast($event.detail)"
    x-cloak
    x-show="visible"
    x-transition:leave.opacity.duration.200ms
    class="pointer-events-none fixed inset-x-0 top-20 z-50 mx-auto flex w-full max-w-6xl justify-end px-4 sm:px-6"
    :role="type === 'warning' ? 'alert' : 'status'"
    aria-live="polite"
>
    <div
        class="pointer-events-auto relative flex w-full items-start gap-3 overflow-hidden rounded-2xl border bg-white p-4 shadow-lg ring-1 ring-black/5 sm:max-w-sm dark:bg-stone-800"
        :class="[
            type === 'warning'
                ? 'border-amber-300 dark:border-amber-700'
                : 'border-emerald-200 dark:border-emerald-800',
            destacado
                ? (secuencia % 2 === 0 ? 'toast-pop-a' : 'toast-pop-b')
                : '',
        ]"
    >
        <span
            class="flex size-9 shrink-0 items-center justify-center rounded-xl"
            :class="type === 'warning'
                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300'
                : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'"
            aria-hidden="true"
        >
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path :d="type === 'warning' ? 'M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z' : 'm5 12 4 4L19 6'" />
            </svg>
        </span>

        <p class="min-w-0 flex-1 pt-1.5 text-sm font-medium text-stone-800 dark:text-stone-100" x-text="message"></p>

        <button
            type="button"
            x-on:click="visible = false; clearTimeout(timer)"
            class="flex size-8 shrink-0 items-center justify-center rounded-lg text-stone-400 hover:bg-stone-100 hover:text-stone-700 focus:outline-none focus:ring-2 dark:text-stone-500 dark:hover:bg-stone-700 dark:hover:text-stone-200"
            :class="type === 'warning' ? 'focus:ring-amber-500' : 'focus:ring-emerald-500'"
            aria-label="Cerrar notificación"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>

        <div
            x-show="destacado"
            class="absolute inset-x-0 bottom-0 h-1"
            :class="[
                type === 'warning' ? 'bg-amber-500' : 'bg-emerald-500',
                secuencia % 2 === 0 ? 'toast-barra-a' : 'toast-barra-b',
            ]"
            aria-hidden="true"
        ></div>
    </div>
</div>
