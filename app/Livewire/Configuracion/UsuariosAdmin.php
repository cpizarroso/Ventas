<?php

namespace App\Livewire\Configuracion;

use App\Enums\Rol;
use App\Livewire\Concerns\FlashesSavedStatus;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UsuariosAdmin extends Component
{
    use FlashesSavedStatus, WithPagination;

    public string $nombre = '';

    public string $cedula = '';

    public string $telefono = '';

    public ?string $email = null;

    public ?string $direccion = null;

    public string $usuario = '';

    public string $rol = Rol::Cajero->value;

    public string $password = '';

    public string $password_confirmation = '';

    public ?int $editandoId = null;

    public bool $modalAbierto = false;

    public bool $modalReporteAbierto = false;

    public bool $modalResetAbierto = false;

    public bool $modalBajaAbierto = false;

    public bool $reactivando = false;

    public ?int $bajaUsuarioId = null;

    public ?string $bajaNombre = null;

    public ?int $resetUsuarioId = null;

    public ?string $resetNombre = null;

    public ?string $resetUsuario = null;

    public string $passwordReset = '';

    public ?string $passwordGenerada = null;

    public ?string $reporteNombre = null;

    public ?string $reporteUsuario = null;

    public ?string $reporteFecha = null;

    public ?string $reporteMensaje = null;

    #[Url(as: 'buscar-usuarios')]
    public string $buscar = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function placeholder(): View
    {
        return view('components.configuracion-modulo-placeholder', ['titulo' => 'usuarios']);
    }

    /**
     * Alias the paginator so this component does not clash with the other configuration lists.
     *
     * @return array<string, array<string, mixed>>
     */
    public function queryString(): array
    {
        return [
            'paginators.page' => ['as' => 'pagina-usuarios', 'history' => false],
        ];
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function abrirModal(): void
    {
        $this->authorize('create', User::class);

        $this->resetFormulario();
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->resetFormulario();
    }

    /**
     * Create a user or persist the one being edited.
     */
    public function guardar(): void
    {
        $negocio = $this->negocioDelUsuario();
        $editando = $this->editandoId !== null
            ? $this->usuarioDelNegocio($this->editandoId)
            : null;

        $editando !== null
            ? $this->authorize('update', $editando)
            : $this->authorize('create', User::class);

        $this->normalizarDatos();

        if ($editando !== null && $editando->id === Auth::id() && $this->rol !== Rol::Admin->value) {
            $this->addError('rol', 'No puedes quitarte el rol de administrador.');

            return;
        }

        $this->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:120'],
            'cedula' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'cedula')
                    ->where('negocio_id', $negocio->id)
                    ->ignore($editando?->id),
            ],
            'telefono' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editando?->id)],
            'direccion' => ['nullable', 'string', 'max:255'],
            'usuario' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($editando?->id),
            ],
            'rol' => ['required', Rule::in([Rol::Admin->value, Rol::Cajero->value])],
            'password' => $editando === null
                ? ['required', 'string', 'min:8', 'confirmed']
                : ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'cedula.unique' => 'Ya existe un usuario con esa cédula en este negocio.',
            'usuario.regex' => 'El usuario solo puede contener letras minúsculas, números, puntos, guiones y guiones bajos.',
            'usuario.unique' => 'Ese nombre de usuario ya está en uso.',
            'email.unique' => 'Ese correo electrónico ya está registrado.',
            'telefono.regex' => 'El teléfono solo puede contener números, espacios y los signos + ( ) -.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ], [
            'nombre' => 'nombre completo',
            'cedula' => 'cédula de identidad',
            'telefono' => 'número de teléfono',
            'email' => 'correo electrónico',
            'direccion' => 'dirección',
            'usuario' => 'usuario',
            'rol' => 'rol',
            'password' => 'contraseña',
        ]);

        $datos = [
            'name' => $this->nombre,
            'cedula' => $this->cedula,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'username' => $this->usuario,
            'rol' => $this->rol,
        ];

        if ($this->password !== '') {
            $datos['password'] = $this->password;
        }

        if ($editando !== null) {
            $editando->update($datos);
        } else {
            $negocio->usuarios()->create($datos);
        }

        $this->flashSaved();
        $this->resetFormulario();
    }

    /**
     * Load a user of the business into the edit form.
     */
    public function iniciarEdicion(int $usuarioId): void
    {
        $usuario = $this->usuarioDelNegocio($usuarioId);

        $this->authorize('update', $usuario);

        $this->editandoId = $usuario->id;
        $this->nombre = $usuario->name;
        $this->cedula = $usuario->cedula ?? '';
        $this->telefono = $usuario->telefono ?? '';
        $this->email = $usuario->email;
        $this->direccion = $usuario->direccion;
        $this->usuario = $usuario->username ?? '';
        $this->rol = $usuario->rol->value;
        $this->password = '';
        $this->password_confirmation = '';
        $this->cancelarBaja();
        $this->modalAbierto = true;

        $this->resetValidation();
    }

    public function cancelarEdicion(): void
    {
        $this->resetFormulario();
    }

    /**
     * Ask for confirmation before deactivating a user.
     */
    public function confirmarBaja(int $usuarioId): void
    {
        $usuario = $this->usuarioDelNegocio($usuarioId);

        $this->authorize('update', $usuario);

        abort_if($usuario->id === Auth::id(), 403, 'No puedes darte de baja a ti mismo.');

        $this->resetFormulario();
        $this->bajaUsuarioId = $usuario->id;
        $this->bajaNombre = $usuario->name;
        $this->reactivando = false;
        $this->modalBajaAbierto = true;

        $this->resetValidation();
    }

    /**
     * Ask for confirmation before reactivating a deactivated user.
     */
    public function confirmarReactivacion(int $usuarioId): void
    {
        $usuario = $this->usuarioDelNegocio($usuarioId);

        $this->authorize('update', $usuario);

        abort_if($usuario->id === Auth::id(), 403, 'No puedes reactivarte a ti mismo.');
        abort_if($usuario->activo, 422, 'Este usuario ya está activo.');

        $this->resetFormulario();
        $this->bajaUsuarioId = $usuario->id;
        $this->bajaNombre = $usuario->name;
        $this->reactivando = true;
        $this->modalBajaAbierto = true;

        $this->resetValidation();
    }

    public function cancelarBaja(): void
    {
        $this->modalBajaAbierto = false;
        $this->reactivando = false;
        $this->bajaUsuarioId = null;
        $this->bajaNombre = null;
    }

    /**
     * Deactivate the confirmed user without deleting any record.
     */
    public function darDeBaja(): void
    {
        $usuario = $this->usuarioDelNegocio((int) $this->bajaUsuarioId);

        $this->authorize('update', $usuario);

        abort_if($this->reactivando, 422, 'La acción confirmada no es una baja.');
        abort_if($usuario->id === Auth::id(), 403, 'No puedes darte de baja a ti mismo.');
        abort_if(! $usuario->activo, 422, 'Este usuario ya está dado de baja.');

        $usuario->update(['activo' => false]);

        $this->cancelarBaja();

        session()->flash('status', 'El usuario se dio de baja correctamente.');
        $this->dispatch('toast', message: 'El usuario se dio de baja correctamente.');
    }

    /**
     * Reactivate the confirmed user without deleting any record.
     */
    public function reactivar(): void
    {
        $usuario = $this->usuarioDelNegocio((int) $this->bajaUsuarioId);

        $this->authorize('update', $usuario);

        abort_unless($this->reactivando, 422, 'La acción confirmada no es una reactivación.');
        abort_if($usuario->id === Auth::id(), 403, 'No puedes reactivarte a ti mismo.');
        abort_if($usuario->activo, 422, 'Este usuario ya está activo.');

        $usuario->update(['activo' => true]);

        $this->cancelarBaja();

        session()->flash('status', 'El usuario se reactivó correctamente.');
        $this->dispatch('toast', message: 'El usuario se reactivó correctamente.');
    }

    /**
     * Open the reset form with an editable eight-digit password proposal.
     */
    public function abrirResetPassword(int $usuarioId): void
    {
        $usuario = $this->usuarioDelNegocio($usuarioId);

        $this->authorize('update', $usuario);

        $this->cancelarBaja();
        $this->modalAbierto = false;
        $this->modalReporteAbierto = false;
        $this->resetUsuarioId = $usuario->id;
        $this->resetNombre = $usuario->name;
        $this->resetUsuario = $usuario->username ?? '';
        $this->passwordReset = $this->generarPasswordAleatoria();
        $this->modalResetAbierto = true;

        $this->resetValidation();
    }

    /**
     * Replace the proposed password with a new random eight-digit number.
     */
    public function generarPasswordReset(): void
    {
        $this->passwordReset = $this->generarPasswordAleatoria();
        $this->resetValidation();
    }

    public function cancelarResetPassword(): void
    {
        $this->modalResetAbierto = false;
        $this->resetUsuarioId = null;
        $this->resetNombre = null;
        $this->resetUsuario = null;
        $this->passwordReset = '';

        $this->resetValidation();
    }

    /**
     * Persist the chosen password and show the report the admin can copy.
     */
    public function guardarResetPassword(): void
    {
        $this->validate([
            'passwordReset' => ['required', 'string', 'digits:8'],
        ], [
            'passwordReset.required' => 'La contraseña temporal es obligatoria.',
            'passwordReset.digits' => 'La contraseña temporal debe tener exactamente 8 dígitos.',
        ], [
            'passwordReset' => 'contraseña temporal',
        ]);

        $usuario = $this->usuarioDelNegocio((int) $this->resetUsuarioId);

        $this->authorize('update', $usuario);

        $usuario->update(['password' => $this->passwordReset]);

        $fecha = now()->format('d/m/Y H:i');
        $this->passwordGenerada = $this->passwordReset;
        $this->reporteNombre = $usuario->name;
        $this->reporteUsuario = $usuario->username ?? '';
        $this->reporteFecha = $fecha;
        $this->reporteMensaje = "Hola {$usuario->name}, se restableció tu contraseña el {$fecha}.\n\n"
            ."Usuario: {$usuario->username}\n"
            ."Contraseña temporal: {$this->passwordReset}\n\n"
            .'Por seguridad, debes actualizar tu contraseña después de iniciar sesión.';

        $this->cancelarResetPassword();
        $this->modalReporteAbierto = true;
    }

    public function cerrarReporte(): void
    {
        $this->modalReporteAbierto = false;
        $this->passwordGenerada = null;
        $this->reporteNombre = null;
        $this->reporteUsuario = null;
        $this->reporteFecha = null;
        $this->reporteMensaje = null;
        $this->cancelarResetPassword();
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;
        $buscar = trim($this->buscar);

        $usuarios = ($negocio !== null ? $negocio->usuarios() : User::query()->whereRaw('1 = 0'))
            ->when($buscar !== '', function ($query) use ($buscar): void {
                $patron = '%'.$buscar.'%';

                $query->where(function ($query) use ($patron): void {
                    $query->where('name', 'like', $patron)
                        ->orWhere('username', 'like', $patron)
                        ->orWhere('email', 'like', $patron)
                        ->orWhere('cedula', 'like', $patron);
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.configuracion.usuarios-admin', [
            'usuarios' => $usuarios,
            'roles' => [Rol::Admin, Rol::Cajero],
            'usuarioActualId' => Auth::id(),
        ]);
    }

    private function normalizarDatos(): void
    {
        $this->nombre = trim($this->nombre);
        $this->cedula = Str::upper(trim($this->cedula));
        $this->telefono = trim($this->telefono);
        $this->usuario = Str::lower(trim($this->usuario));
        $this->email = $this->email !== null && trim($this->email) !== ''
            ? Str::lower(trim($this->email))
            : null;
        $this->direccion = $this->direccion !== null && trim($this->direccion) !== ''
            ? trim($this->direccion)
            : null;
    }

    private function resetFormulario(): void
    {
        $this->reset(
            'nombre',
            'cedula',
            'telefono',
            'email',
            'direccion',
            'usuario',
            'password',
            'password_confirmation',
            'editandoId',
            'modalAbierto',
            'modalBajaAbierto',
            'reactivando',
            'bajaUsuarioId',
            'bajaNombre',
            'modalResetAbierto',
            'resetUsuarioId',
            'resetNombre',
            'resetUsuario',
            'passwordReset',
            'passwordGenerada',
            'reporteNombre',
            'reporteUsuario',
            'reporteFecha',
            'reporteMensaje',
        );

        $this->rol = Rol::Cajero->value;
        $this->modalReporteAbierto = false;

        $this->resetValidation();
    }

    private function generarPasswordAleatoria(): string
    {
        return str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    private function negocioDelUsuario(): Negocio
    {
        $negocio = Auth::user()?->negocio;

        abort_if($negocio === null, 403, 'Tu usuario no tiene un negocio asignado.');

        return $negocio;
    }

    private function usuarioDelNegocio(int $usuarioId): User
    {
        $usuario = $this->negocioDelUsuario()
            ->usuarios()
            ->whereKey($usuarioId)
            ->first();

        abort_if($usuario === null, 404);

        return $usuario;
    }
}
