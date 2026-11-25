<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-700 dark:bg-stone-800 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Usuarios del negocio</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Crea administradores y cajeros, y administra sus datos de acceso.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        wire:click="abrirModal"
                        class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:bg-amber-400">
                    Nuevo usuario
                </button>
                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                    {{ $usuarios->total() }} {{ $usuarios->total() === 1 ? 'usuario' : 'usuarios' }}
                </span>
            </div>
        </div>

        <div class="mt-5 max-w-sm">
            <label for="buscar-usuarios" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                Buscar usuario
            </label>
            <input id="buscar-usuarios"
                   type="search"
                   wire:model.live.debounce.300ms="buscar"
                   placeholder="Nombre, usuario, correo o cédula"
                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
        </div>

        <x-modal-configuracion id="modal-usuario"
                               :titulo="$editandoId === null ? 'Nuevo usuario' : 'Editar usuario'"
                               descripcion="Completa los datos de acceso y la información del usuario."
                               cerrar="cerrarModal"
                               ancho="max-w-4xl"
                               :abierto="$modalAbierto">
            <form wire:submit="guardar" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                    {{ $editandoId === null ? 'Nuevo usuario' : 'Editar usuario' }}
                </h3>
                @if ($editandoId !== null)
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        Editando
                    </span>
                @endif
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="usuario-nombre" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Nombre completo <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="usuario-nombre"
                           type="text"
                           wire:model="nombre"
                           required
                           autocomplete="name"
                           placeholder="Ej: Ana Pérez"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('nombre')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-cedula" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Cédula de identidad <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="usuario-cedula"
                           type="text"
                           wire:model="cedula"
                           required
                           placeholder="Ej: 12345678"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm uppercase shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Debe ser única dentro del negocio.</p>
                    @error('cedula')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-telefono" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Número de teléfono <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="usuario-telefono"
                           type="tel"
                           wire:model="telefono"
                           required
                           autocomplete="tel"
                           placeholder="Ej: 70012345"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('telefono')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Correo electrónico <span class="text-xs font-normal normal-case text-stone-400">(opcional)</span>
                    </label>
                    <input id="usuario-email"
                           type="email"
                           wire:model="email"
                           autocomplete="email"
                           placeholder="Ej: ana@negocio.com"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="usuario-direccion" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Dirección <span class="text-xs font-normal normal-case text-stone-400">(opcional)</span>
                    </label>
                    <input id="usuario-direccion"
                           type="text"
                           wire:model="direccion"
                           autocomplete="street-address"
                           placeholder="Ej: Av. Principal #123"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('direccion')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-login" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Usuario <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="usuario-login"
                           type="text"
                           wire:model="usuario"
                           required
                           autocomplete="username"
                           placeholder="Ej: ana.perez"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm lowercase shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Se usará para iniciar sesión junto con la contraseña.</p>
                    @error('usuario')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-rol" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Rol <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <select id="usuario-rol"
                            wire:model="rol"
                            required
                            @disabled($editandoId === $usuarioActualId)
                            class="selector-tema disabled:cursor-not-allowed disabled:bg-stone-100 dark:disabled:bg-stone-800">
                        @foreach ($roles as $opcion)
                            <option value="{{ $opcion->value }}">{{ $opcion->etiqueta() }}</option>
                        @endforeach
                    </select>
                    @if ($editandoId === $usuarioActualId)
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">No puedes cambiar tu propio rol.</p>
                    @endif
                    @error('rol')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Contraseña @if ($editandoId === null)<span class="text-red-500" aria-hidden="true">*</span>@endif
                    </label>
                    <div class="relative">
                        <input id="usuario-password"
                               type="password"
                               wire:model="password"
                               @if ($editandoId === null) required @endif
                               autocomplete="new-password"
                               placeholder="{{ $editandoId === null ? 'Mínimo 8 caracteres' : 'Dejar en blanco para conservar' }}"
                               class="w-full rounded-xl border border-stone-300 bg-white pl-3 py-2 pr-11 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                        <x-password-toggle target="usuario-password" />
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-password-confirmation" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Confirmar contraseña @if ($editandoId === null)<span class="text-red-500" aria-hidden="true">*</span>@endif
                    </label>
                    <div class="relative">
                        <input id="usuario-password-confirmation"
                               type="password"
                               wire:model="password_confirmation"
                               @if ($editandoId === null) required @endif
                               autocomplete="new-password"
                               placeholder="Repite la contraseña"
                               class="w-full rounded-xl border border-stone-300 bg-white pl-3 py-2 pr-11 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                        <x-password-toggle target="usuario-password-confirmation" />
                    </div>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-2">
                @if ($editandoId !== null)
                    <button type="button"
                            wire:click="cancelarEdicion"
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Cancelar
                    </button>
                @endif
                <button type="submit"
                        class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                    Guardar cambios
                </button>
            </div>
            </form>
        </x-modal-configuracion>

        <x-modal-configuracion id="modal-reset-password"
                               titulo="Restablecer contraseña"
                               descripcion="El sistema propone una contraseña de 8 dígitos, pero puedes editarla antes de guardar."
                               cerrar="cancelarResetPassword"
                               :abierto="$modalResetAbierto"
                               ancho="max-w-md">
            <form wire:submit="guardarResetPassword" class="space-y-4">
                <p class="text-sm text-stone-600 dark:text-stone-300">
                    Se restablecerá la contraseña de
                    <strong class="font-semibold">{{ $resetNombre }}</strong>
                    <span class="text-stone-500 dark:text-stone-400">({{ $resetUsuario }})</span>.
                </p>

                <div>
                    <label for="password-reset" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Contraseña temporal <span class="text-red-500" aria-hidden="true">*</span>
                    </label>
                    <input id="password-reset"
                           type="text"
                           inputmode="numeric"
                           maxlength="8"
                           wire:model="passwordReset"
                           autocomplete="off"
                           placeholder="8 dígitos"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 font-mono text-base tracking-widest shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Puedes modificar el número generado.</p>
                    @error('passwordReset')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button"
                            wire:click="generarPasswordReset"
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Generar otro número
                    </button>
                    <button type="button"
                            wire:click="cancelarResetPassword"
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-stone-400 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-xl bg-amber-500 px-5 py-2.5 text-sm font-semibold text-stone-900 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:focus:ring-offset-stone-900">
                        Guardar
                    </button>
                </div>
            </form>
        </x-modal-configuracion>

        <x-modal-configuracion id="modal-reporte-password"
                               titulo="Contraseña restablecida"
                               descripcion="Confirma el restablecimiento y copia el mensaje para enviarlo al usuario."
                               cerrar="cerrarReporte"
                               :abierto="$modalReporteAbierto"
                               ancho="max-w-lg">
            <div class="space-y-4">
                <p class="text-sm text-stone-600 dark:text-stone-300">
                    Se restableció la contraseña de
                    <strong class="font-semibold">{{ $reporteNombre }}</strong>.
                </p>

                <dl class="grid gap-2 rounded-xl border border-stone-200 bg-stone-50 p-3 text-sm dark:border-stone-700 dark:bg-stone-900/50">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-stone-500 dark:text-stone-400">Usuario</dt>
                        <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $reporteUsuario }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-stone-500 dark:text-stone-400">Contraseña temporal</dt>
                        <dd class="font-mono text-base font-semibold tracking-widest text-stone-900 dark:text-stone-100">{{ $passwordGenerada }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-stone-500 dark:text-stone-400">Fecha del reseteo</dt>
                        <dd class="font-semibold text-stone-800 dark:text-stone-100">{{ $reporteFecha }}</dd>
                    </div>
                </dl>

                <div>
                    <label for="reporte-mensaje" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Mensaje para enviar
                    </label>
                    <textarea id="reporte-mensaje"
                              rows="5"
                              readonly
                              class="w-full resize-none rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100">{{ $reporteMensaje }}</textarea>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button"
                            wire:click="cerrarReporte"
                            data-preservar-scroll
                            class="rounded-xl border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Cerrar
                    </button>
                    <button type="button"
                            x-on:click="window.copiarReporte($event.currentTarget)"
                            class="rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-stone-900 hover:bg-amber-400">
                        Copiar mensaje
                    </button>
                </div>
            </div>
        </x-modal-configuracion>

        <x-modal-configuracion id="modal-baja-usuario"
                               :titulo="$reactivando ? 'Reactivar usuario' : 'Dar de baja usuario'"
                               :descripcion="$reactivando ? 'El usuario podrá volver a iniciar sesión.' : 'El usuario no podrá iniciar sesión.'"
                               cerrar="cancelarBaja"
                               :abierto="$modalBajaAbierto"
                               ancho="max-w-md">
            <div class="space-y-4">
                <div @class([
                    'rounded-xl border px-4 py-3',
                    'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-900/20' => $reactivando,
                    'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-900/20' => ! $reactivando,
                ])>
                    <p @class([
                        'text-sm font-semibold',
                        'text-emerald-800 dark:text-emerald-300' => $reactivando,
                        'text-red-800 dark:text-red-300' => ! $reactivando,
                    ])>
                        @if ($reactivando)
                            ¿Reactivar a «{{ $bajaNombre }}»?
                        @else
                            ¿Dar de baja a «{{ $bajaNombre }}»?
                        @endif
                    </p>
                    <p @class([
                        'mt-1 text-sm',
                        'text-emerald-700 dark:text-emerald-300' => $reactivando,
                        'text-red-700 dark:text-red-300' => ! $reactivando,
                    ])>
                        @if ($reactivando)
                            El usuario podrá iniciar sesión de nuevo con su contraseña actual. Sus datos se conservan sin cambios.
                        @else
                            El usuario no podrá iniciar sesión y su sesión activa se cerrará automáticamente. Sus datos y ventas históricas se conservan.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button"
                            wire:click="cancelarBaja"
                            data-preservar-scroll
                            class="rounded-xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-700">
                        Cancelar
                    </button>
                    @if ($reactivando)
                        <button type="button"
                                wire:click="reactivar"
                                data-preservar-scroll
                                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                            Sí, reactivar
                        </button>
                    @else
                        <button type="button"
                                wire:click="darDeBaja"
                                data-preservar-scroll
                                class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-stone-800">
                            Sí, dar de baja
                        </button>
                    @endif
                </div>
            </div>
        </x-modal-configuracion>

        <div class="mt-6">
            @if ($usuarios->isEmpty())
                <div class="rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Todavía no hay usuarios en este negocio</p>
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Crea el primero con el botón «Nuevo usuario».</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-stone-100 text-sm dark:divide-stone-700/60">
                        <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:bg-stone-900/40 dark:text-stone-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">Usuario</th>
                                <th scope="col" class="px-4 py-3">Cédula</th>
                                <th scope="col" class="px-4 py-3">Contacto</th>
                                <th scope="col" class="px-4 py-3">Rol</th>
                                <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                            @foreach ($usuarios as $usuario)
                                <tr wire:key="usuario-{{ $usuario->id }}">
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-stone-800 dark:text-stone-100">{{ $usuario->name }}</span>
                                            @if ($usuario->id === $usuarioActualId)
                                                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-[11px] font-semibold text-stone-500 dark:bg-stone-700 dark:text-stone-300">Tú</span>
                                            @endif
                                            @if (! $usuario->activo)
                                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">Inactivo</span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">@ {{ $usuario->username }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                        {{ $usuario->cedula ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-stone-600 dark:text-stone-300">
                                        <p class="break-words">{{ $usuario->telefono ?: '—' }}</p>
                                        @if ($usuario->email)
                                            <p class="break-words text-xs text-stone-500 dark:text-stone-400">{{ $usuario->email }}</p>
                                        @endif
                                        @if ($usuario->direccion)
                                            <p class="break-words text-xs text-stone-500 dark:text-stone-400">{{ $usuario->direccion }}</p>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <span @class([
                                            'rounded-full px-2 py-1 text-xs font-semibold',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' => $usuario->rol->value === 'admin',
                                            'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' => $usuario->rol->value === 'cajero',
                                        ])>
                                            {{ $usuario->rol->etiqueta() }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button type="button"
                                                    wire:click="iniciarEdicion({{ $usuario->id }})"
                                                    data-preservar-scroll
                                                    title="Editar {{ $usuario->name }}"
                                                    aria-label="Editar {{ $usuario->name }}"
                                                    class="rounded-lg p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 0 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    wire:click="abrirResetPassword({{ $usuario->id }})"
                                                    data-preservar-scroll
                                                    title="Restablecer contraseña de {{ $usuario->name }}"
                                                    aria-label="Restablecer contraseña de {{ $usuario->name }}"
                                                    class="rounded-lg p-2 text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/30">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                                </svg>
                                            </button>
                                            @if ($usuario->activo && $usuario->id !== $usuarioActualId)
                                                <button type="button"
                                                        wire:click="confirmarBaja({{ $usuario->id }})"
                                                        data-preservar-scroll
                                                        title="Dar de baja a {{ $usuario->name }}"
                                                        aria-label="Dar de baja a {{ $usuario->name }}"
                                                        class="rounded-lg p-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/40">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636 5.636 18.364M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
                                                    </svg>
                                                </button>
                                            @endif
                                            @if (! $usuario->activo && $usuario->id !== $usuarioActualId)
                                                <button type="button"
                                                        wire:click="confirmarReactivacion({{ $usuario->id }})"
                                                        data-preservar-scroll
                                                        title="Reactivar a {{ $usuario->name }}"
                                                        aria-label="Reactivar a {{ $usuario->name }}"
                                                        class="rounded-lg p-2 text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/30">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $usuarios->links('components.paginacion-compacta', ['enLivewire' => true]) }}
            @endif
        </div>
    </div>
</div>
