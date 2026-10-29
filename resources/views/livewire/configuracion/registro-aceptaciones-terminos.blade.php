<div>
    <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-700 dark:bg-stone-800">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100">Aceptaciones registradas</h2>
                <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                    Usuarios que aceptaron los términos y condiciones, con la versión, fecha, IP y navegador.
                </p>
            </div>
            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-semibold text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                {{ $aceptaciones->total() }} {{ $aceptaciones->total() === 1 ? 'aceptación' : 'aceptaciones' }}
            </span>
        </div>

        <div class="mt-5 max-w-sm">
            <label for="buscar-aceptaciones" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                Buscar usuario
            </label>
            <input id="buscar-aceptaciones"
                   type="search"
                   wire:model.live.debounce.300ms="buscar"
                   placeholder="Nombre, usuario o correo"
                   class="w-full rounded-xl border border-stone-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-stone-600 dark:bg-stone-950 dark:text-stone-100 dark:focus:border-amber-500">
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-stone-200 text-sm dark:divide-stone-700">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">
                        <th scope="col" class="whitespace-nowrap px-3 py-3">Usuario</th>
                        @if ($esSuperadmin)
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Negocio</th>
                        @endif
                        <th scope="col" class="whitespace-nowrap px-3 py-3">Rol</th>
                        <th scope="col" class="whitespace-nowrap px-3 py-3">Versión</th>
                        <th scope="col" class="whitespace-nowrap px-3 py-3">Aceptado el</th>
                        <th scope="col" class="whitespace-nowrap px-3 py-3">IP</th>
                        <th scope="col" class="whitespace-nowrap px-3 py-3">Navegador</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-stone-700/60">
                    @forelse ($aceptaciones as $aceptacion)
                        <tr wire:key="aceptacion-{{ $aceptacion->id }}" class="align-top">
                            <td class="px-3 py-3">
                                <p class="font-medium text-stone-900 dark:text-stone-100">
                                    {{ $aceptacion->user?->name ?? 'Usuario eliminado' }}
                                </p>
                                <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                    {{ $aceptacion->user?->username }}
                                    @if ($aceptacion->user?->email)
                                        <span aria-hidden="true">·</span> {{ $aceptacion->user->email }}
                                    @endif
                                </p>
                            </td>
                            @if ($esSuperadmin)
                                <td class="whitespace-nowrap px-3 py-3 text-stone-600 dark:text-stone-300">
                                    {{ $aceptacion->user?->negocio?->nombre_comercial ?? '—' }}
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-3 py-3 text-stone-600 dark:text-stone-300">
                                {{ $aceptacion->user?->rol?->etiqueta() ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3">
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                    {{ $aceptacion->version }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-stone-600 dark:text-stone-300">
                                {{ $aceptacion->aceptado_en?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-stone-600 dark:text-stone-300">
                                {{ $aceptacion->ip_address ?? '—' }}
                            </td>
                            <td class="max-w-xs px-3 py-3 text-stone-600 dark:text-stone-300">
                                <span class="block truncate" title="{{ $aceptacion->user_agent }}">
                                    {{ $aceptacion->user_agent ? \Illuminate\Support\Str::limit($aceptacion->user_agent, 48) : '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $esSuperadmin ? 7 : 6 }}" class="px-3 py-8 text-center text-sm text-stone-500 dark:text-stone-400">
                                No hay aceptaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($aceptaciones->hasPages())
            <div class="mt-5">
                {{ $aceptaciones->links() }}
            </div>
        @endif
    </div>
</div>
