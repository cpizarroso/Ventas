<?php

namespace App\Livewire\Configuracion;

use App\Models\AceptacionTerminos;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RegistroAceptacionesTerminos extends Component
{
    use WithPagination;

    #[Url(as: 'buscar')]
    public string $buscar = '';

    public function mount(): void
    {
        $this->authorize('gestionar-configuracion');
    }

    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $usuario = auth()->user();

        abort_unless($usuario instanceof User, 403);

        $aceptaciones = AceptacionTerminos::query()
            ->with(['user.negocio'])
            ->when(! $usuario->esSuperadmin(), function ($query) use ($usuario): void {
                if ($usuario->negocio_id === null) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereHas('user', fn ($user) => $user->where('negocio_id', $usuario->negocio_id));
            })
            ->when(trim($this->buscar) !== '', function ($query): void {
                $buscar = '%'.trim($this->buscar).'%';

                $query->whereHas('user', function ($user) use ($buscar): void {
                    $user->where('name', 'like', $buscar)
                        ->orWhere('username', 'like', $buscar)
                        ->orWhere('email', 'like', $buscar);
                });
            })
            ->orderByDesc('aceptado_en')
            ->paginate(20);

        return view('livewire.configuracion.registro-aceptaciones-terminos', [
            'aceptaciones' => $aceptaciones,
            'esSuperadmin' => $usuario->esSuperadmin(),
        ]);
    }
}
