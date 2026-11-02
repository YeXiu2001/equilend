<?php

declare(strict_types=1);

namespace App\Livewire\PaymentMethods;

use Livewire\Component;
use App\Models\PaymentMethod;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Renderless;

class Delete extends Component
{
    use Alert;

    public PaymentMethod $paymentMethod;

    public function render(): string
    {
        return <<<'HTML'
        <div>
            <button type="button" class="btn btn-ghost btn-circle btn-sm text-error" wire:click="confirm" aria-label="Delete payment method">
                <x-heroicon-o-trash class="w-4 h-4" />
            </button>
        </div>
        HTML;
    }

    #[Renderless]
    public function confirm(): void
    {
        $this->question("Are you sure you want to delete payment method {$this->paymentMethod->name}?")
            ->confirm(method: 'delete')
            ->cancel()
            ->send();
    }

    public function delete(): void
    {
        $this->paymentMethod->delete();

        $this->dispatch('deleted');

        $this->success('Payment method deleted successfully.');
    }
}
