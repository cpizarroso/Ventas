<div class="relative" x-data="{ menuAbierto: false }"
     x-on:click.outside="menuAbierto = false"
     x-on:keydown.escape.window="menuAbierto = false">
    <button type="button"
            x-on:click="menuAbierto = ! menuAbierto"
            x-bind:aria-expanded="menuAbierto ? 'true' : 'false'"
            aria-haspopup="menu"
            aria-controls="menu-usuario-opciones"
            aria-label="Abrir menú de {{ $usuario->name }}"
            title="Menú de usuario"
            class="flex items-center gap-2 rounded-full border border-stone-200 bg-stone-50 py-1 pl-1 pr-2 transition hover:border-amber-300 hover:bg-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:border-stone-700 dark:bg-stone-700/40 dark:hover:border-amber-600/70 dark:hover:bg-stone-700/70">
        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-amber-500 text-xs font-bold uppercase text-stone-900" aria-hidden="true">
            {{ mb_substr(trim($usuario->name), 0, 1) }}
        </span>
        <span class="hidden min-w-0 text-left sm:block">
            <span class="block max-w-28 truncate text-xs font-semibold text-stone-700 dark:text-stone-200 sm:max-w-32 sm:text-sm">
                {{ $usuario->name }}
            </span>
            <span class="mt-0.5 block max-w-28 truncate text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400 sm:max-w-32">
                {{ $usuario->rol?->etiqueta() }}
            </span>
        </span>
        <svg x-bind:class="menuAbierto ? 'rotate-180' : ''"
             class="size-3.5 shrink-0 text-stone-500 transition-transform duration-150 dark:text-stone-400"
             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div id="menu-usuario-opciones"
         x-show="menuAbierto"
         x-cloak
         x-transition.opacity.duration.150ms
         role="menu"
         aria-label="Opciones de usuario"
         class="absolute right-0 z-40 mt-2 w-64 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-xl dark:border-stone-700 dark:bg-stone-800">
        <div class="border-b border-stone-200 px-4 py-3 dark:border-stone-700">
            <p class="truncate text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $usuario->name }}</p>
            <p class="truncate text-xs text-stone-500 dark:text-stone-400">&#64;{{ $usuario->username }}</p>
            <p class="mt-1 truncate text-[11px] font-semibold text-amber-700 dark:text-amber-400">
                {{ $usuario->rol?->etiqueta() }}
            </p>
        </div>

        <div class="p-1.5">
            <button type="button"
                    x-on:click="menuAbierto = false; $wire.abrirPerfil()"
                    role="menuitem"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium text-stone-700 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus-visible:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700 dark:hover:text-white dark:focus-visible:bg-stone-700">
                <svg class="size-4 shrink-0 text-stone-500 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.25" />
                    <path d="M5 20a7 7 0 0 1 14 0" />
                </svg>
                Mi perfil
            </button>

            <button type="button"
                    x-on:click="menuAbierto = false; $wire.abrirPassword()"
                    role="menuitem"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium text-stone-700 hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus-visible:bg-stone-100 dark:text-stone-200 dark:hover:bg-stone-700 dark:hover:text-white dark:focus-visible:bg-stone-700">
                <svg class="size-4 shrink-0 text-stone-500 dark:text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
                Actualizar contraseña
            </button>
        </div>

        <div class="border-t border-stone-200 p-1.5 dark:border-stone-700">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        role="menuitem"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:text-red-300 dark:focus-visible:bg-red-900/30">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 5h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3" />
                        <path d="M10 8 6 12l4 4M6 12h9" />
                    </svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    <x-modal-configuracion id="modal-perfil-usuario"
                           titulo="Mi perfil"
                           descripcion="Datos de tu cuenta en el sistema."
                           cerrar="cerrarPerfil"
                           :abierto="$modalPerfilAbierto"
                           ancho="max-w-lg">
        <dl class="grid gap-2 rounded-xl border border-stone-200 bg-stone-50 p-3 text-sm dark:border-stone-700 dark:bg-stone-900/50">
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Nombre completo</dt>
                <dd class="text-right font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->name }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Usuario</dt>
                <dd class="font-semibold text-stone-800 dark:text-stone-100">&#64;{{ $usuario->username }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Rol</dt>
                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->rol?->etiqueta() ?? '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Negocio</dt>
                <dd class="text-right font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->negocio?->nombre_comercial ?? '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Cédula</dt>
                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->cedula ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Teléfono</dt>
                <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->telefono ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Correo electrónico</dt>
                <dd class="break-all text-right font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->email ?: '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-stone-500 dark:text-stone-400">Dirección</dt>
                <dd class="text-right font-semibold text-stone-800 dark:text-stone-100">{{ $usuario->direccion ?: '—' }}</dd>
            </div>
        </dl>

        <div class="mt-4 flex justify-end">
            <button type="button"
                    wire:click="cerrarPerfil"
                    class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                Cerrar
            </button>
        </div>
    </x-modal-configuracion>

    <x-modal-configuracion id="modal-password-usuario"
                           titulo="Actualizar contraseña"
                           descripcion="Confirma tu contraseña actual para establecer una nueva."
                           cerrar="cerrarPassword"
                           :abierto="$modalPasswordAbierto"
                           ancho="max-w-md">
        <form wire:submit="guardarPassword" class="space-y-4">
            <div>
                <label for="password-actual" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                    Contraseña actual <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                    <input id="password-actual"
                           type="password"
                           wire:model="passwordActual"
                           required
                           autocomplete="current-password"
                           placeholder="Tu contraseña actual"
                           class="w-full rounded-xl border border-stone-300 bg-white pl-3 py-2 pr-11 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    <x-password-toggle target="password-actual" />
                </div>
                @error('passwordActual')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-nueva" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                    Nueva contraseña <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                    <input id="password-nueva"
                           type="password"
                           wire:model="passwordNueva"
                           required
                           autocomplete="new-password"
                           placeholder="Mínimo 8 caracteres"
                           class="w-full rounded-xl border border-stone-300 bg-white pl-3 py-2 pr-11 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    <x-password-toggle target="password-nueva" />
                </div>
                @error('passwordNueva')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password-nueva-confirmacion" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                    Confirmar nueva contraseña <span class="text-red-500" aria-hidden="true">*</span>
                </label>
                <div class="relative">
                    <input id="password-nueva-confirmacion"
                           type="password"
                           wire:model="passwordNueva_confirmation"
                           required
                           autocomplete="new-password"
                           placeholder="Repite la nueva contraseña"
                           class="w-full rounded-xl border border-stone-300 bg-white pl-3 py-2 pr-11 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    <x-password-toggle target="password-nueva-confirmacion" />
                </div>
            </div>

            <p class="rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                No compartas tu contraseña. Después de cambiarla, se mantendrá tu sesión activa.
            </p>

            <div class="flex flex-wrap justify-end gap-2">
                <button type="button"
                        wire:click="cerrarPassword"
                        class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                    Cancelar
                </button>
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                    Guardar contraseña
                </button>
            </div>
        </form>
    </x-modal-configuracion>
</div>
