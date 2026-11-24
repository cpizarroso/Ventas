<?php

namespace App\Livewire\Cuenta;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class MenuUsuario extends Component
{
    public bool $modalPerfilAbierto = false;

    public bool $modalPasswordAbierto = false;

    public string $passwordActual = '';

    public string $passwordNueva = '';

    public string $passwordNueva_confirmation = '';

    public function abrirPerfil(): void
    {
        $this->modalPasswordAbierto = false;
        $this->modalPerfilAbierto = true;
    }

    public function cerrarPerfil(): void
    {
        $this->modalPerfilAbierto = false;
    }

    public function abrirPassword(): void
    {
        $this->resetPassword();
        $this->modalPerfilAbierto = false;
        $this->modalPasswordAbierto = true;

        $this->resetValidation();
    }

    public function cerrarPassword(): void
    {
        $this->modalPasswordAbierto = false;
        $this->resetPassword();

        $this->resetValidation();
    }

    /**
     * Change the authenticated user's password after checking the current one.
     */
    public function guardarPassword(): void
    {
        $this->validate([
            'passwordActual' => ['required', 'string'],
            'passwordNueva' => ['required', 'string', 'min:8', 'confirmed', 'different:passwordActual'],
        ], [
            'passwordActual.required' => 'Ingresa tu contraseña actual.',
            'passwordNueva.required' => 'Ingresa la nueva contraseña.',
            'passwordNueva.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'passwordNueva.confirmed' => 'La confirmación de la contraseña no coincide.',
            'passwordNueva.different' => 'La nueva contraseña debe ser diferente de la actual.',
        ], [
            'passwordActual' => 'contraseña actual',
            'passwordNueva' => 'nueva contraseña',
        ]);

        $usuario = Auth::user();

        if (! Hash::check($this->passwordActual, $usuario->password)) {
            $this->addError('passwordActual', 'La contraseña actual es incorrecta.');

            return;
        }

        $usuario->update(['password' => $this->passwordNueva]);

        $this->cerrarPassword();

        $this->dispatch('toast', message: 'Contraseña actualizada correctamente.');
    }

    public function render(): View
    {
        return view('livewire.cuenta.menu-usuario', [
            'usuario' => Auth::user(),
        ]);
    }

    private function resetPassword(): void
    {
        $this->reset('passwordActual', 'passwordNueva', 'passwordNueva_confirmation');
    }
}
