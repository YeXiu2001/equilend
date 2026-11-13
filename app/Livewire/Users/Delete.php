<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Component;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Renderless;

class Delete extends Component
{
    use Alert;

    public User $user;

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <button type="button" class="btn btn-ghost btn-circle btn-sm text-error" wire:click="confirm" aria-label="Delete user">
                <x-heroicon-o-trash class="w-4 h-4" />
            </button>
        </div>
        HTML;
    }

    #[Renderless]
    public function confirm(): void
    {
        $this->question()
            ->confirm(method: 'delete')
            ->cancel()
            ->send();
    }

    public function delete(): void
    {
        $this->user->delete();

        $this->dispatch('deleted');

        $this->success();
    }
}
