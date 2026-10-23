<?php

namespace App\Livewire\Configuracion;

use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UsuariosAdmin extends Component
{
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

    public ?int $eliminandoId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
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

            session()->flash('status', 'El usuario se actualizó correctamente.');
        } else {
            $negocio->usuarios()->create($datos);

            session()->flash('status', 'El usuario se creó correctamente.');
        }

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
        $this->eliminandoId = null;

        $this->resetValidation();
    }

    public function cancelarEdicion(): void
    {
        $this->resetFormulario();
    }

    /**
     * Ask for confirmation before deleting a user.
     */
    public function confirmarEliminacion(int $usuarioId): void
    {
        $usuario = $this->usuarioDelNegocio($usuarioId);

        $this->authorize('delete', $usuario);

        $this->resetFormulario();
        $this->eliminandoId = $usuario->id;
    }

    public function cancelarEliminacion(): void
    {
        $this->eliminandoId = null;
    }

    /**
     * Delete the confirmed user. Historical sales keep their record.
     */
    public function eliminar(): void
    {
        $usuario = $this->usuarioDelNegocio((int) $this->eliminandoId);

        $this->authorize('delete', $usuario);

        $usuario->delete();

        $this->resetFormulario();
        $this->eliminandoId = null;

        session()->flash('status', 'El usuario se eliminó correctamente.');
    }

    public function render(): View
    {
        $negocio = Auth::user()?->negocio;

        return view('livewire.configuracion.usuarios-admin', [
            'usuarios' => $negocio
                ? $negocio->usuarios()->orderBy('name')->get()
                : collect(),
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
        );

        $this->rol = Rol::Cajero->value;

        $this->resetValidation();
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
