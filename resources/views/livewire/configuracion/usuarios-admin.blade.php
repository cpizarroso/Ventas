<div>
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Usuarios del negocio</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Crea administradores y cajeros, y administra sus datos de acceso.
                </p>
            </div>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                {{ $usuarios->count() }} {{ $usuarios->count() === 1 ? 'usuario' : 'usuarios' }}
            </span>
        </div>

        <form wire:submit="guardar" class="mt-5 rounded-xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40 sm:p-5">
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
                            class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 disabled:cursor-not-allowed disabled:bg-stone-100 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:disabled:bg-stone-800 dark:focus:border-amber-500">
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
                    <input id="usuario-password"
                           type="password"
                           wire:model="password"
                           @if ($editandoId === null) required @endif
                           autocomplete="new-password"
                           placeholder="{{ $editandoId === null ? 'Mínimo 8 caracteres' : 'Dejar en blanco para conservar' }}"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="usuario-password-confirmation" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        Confirmar contraseña @if ($editandoId === null)<span class="text-red-500" aria-hidden="true">*</span>@endif
                    </label>
                    <input id="usuario-password-confirmation"
                           type="password"
                           wire:model="password_confirmation"
                           @if ($editandoId === null) required @endif
                           autocomplete="new-password"
                           placeholder="Repite la contraseña"
                           class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
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
                    {{ $editandoId === null ? 'Crear usuario' : 'Guardar cambios' }}
                </button>
            </div>
        </form>

        <div class="mt-6">
            @if ($usuarios->isEmpty())
                <div class="rounded-xl border border-dashed border-stone-300 px-4 py-10 text-center dark:border-stone-600">
                    <p class="text-sm font-medium text-stone-600 dark:text-stone-300">Todavía no hay usuarios en este negocio</p>
                    <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">Crea el primero con el formulario de arriba.</p>
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
                                        </div>
                                        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">@ {{ $usuario->username }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-stone-600 dark:text-stone-300">
                                        {{ $usuario->cedula ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-stone-600 dark:text-stone-300">
                                        <p>{{ $usuario->telefono ?: '—' }}</p>
                                        @if ($usuario->email)
                                            <p class="text-xs text-stone-500 dark:text-stone-400">{{ $usuario->email }}</p>
                                        @endif
                                        @if ($usuario->direccion)
                                            <p class="text-xs text-stone-500 dark:text-stone-400">{{ $usuario->direccion }}</p>
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
                                        <button type="button"
                                                wire:click="iniciarEdicion({{ $usuario->id }})"
                                                class="rounded-lg px-3 py-1.5 text-xs font-semibold text-stone-600 hover:bg-stone-100 hover:text-stone-900 dark:text-stone-300 dark:hover:bg-stone-700 dark:hover:text-white">
                                            Editar
                                        </button>
                                        @if ($usuario->id !== $usuarioActualId)
                                            <button type="button"
                                                    wire:click="confirmarEliminacion({{ $usuario->id }})"
                                                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/40">
                                                Eliminar
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                @if ($eliminandoId === $usuario->id)
                                    <tr wire:key="eliminar-usuario-{{ $usuario->id }}">
                                        <td colspan="5" class="px-4 py-4">
                                            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-900 dark:bg-red-900/30">
                                                <p class="text-sm font-medium text-red-800 dark:text-red-300">
                                                    ¿Eliminar a «{{ $usuario->name }}»? Sus ventas históricas se conservarán, pero el vendedor aparecerá como «—».
                                                </p>
                                                <div class="flex shrink-0 gap-2">
                                                    <button type="button"
                                                            wire:click="eliminar"
                                                            class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500">
                                                        Sí, eliminar
                                                    </button>
                                                    <button type="button"
                                                            wire:click="cancelarEliminacion"
                                                            class="rounded-lg bg-stone-200 px-3 py-1.5 text-xs font-semibold text-stone-700 hover:bg-stone-300 dark:bg-stone-600 dark:text-stone-200 dark:hover:bg-stone-500">
                                                        Cancelar
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
