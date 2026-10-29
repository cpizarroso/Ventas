<?php

namespace App\Livewire\Concerns;

trait FlashesSavedStatus
{
    /**
     * Flash the standard saved message and show it as a toast.
     */
    protected function flashSaved(): void
    {
        session()->flash('status', 'Guardado exitosamente');

        $this->dispatch('toast', message: 'Guardado exitosamente');
    }
}
