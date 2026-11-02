<?php

declare(strict_types=1);

namespace App\Livewire\PaymentMethods;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PaymentMethod;
use App\Livewire\Traits\Alert;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class Create extends Component
{
    use Alert;

    public string $payment_code = '';

    public string $name = '';

    public ?string $description = null;

    public bool $is_active = true;

    public bool $modal = false;

    public function render(): View
    {
        return view('livewire.payment-methods.create');
    }

    #[On('open::create-payment-method')]
    public function open(): void
    {
        $this->reset(['payment_code', 'name', 'description']);
        $this->is_active = true;
        $this->resetValidation();
        $this->modal = true;
    }

    public function rules(): array
    {
        return [
            'payment_code' => ['required', 'string', 'max:50', Rule::unique('payment_methods', 'payment_code')],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:500'],
            'is_active'    => ['boolean'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        PaymentMethod::create([
            'payment_code' => mb_strtolower($validated['payment_code']),
            'name'         => $validated['name'],
            'description'  => $validated['description'],
            'is_active'    => $validated['is_active'],
        ]);

        $this->dispatch('created');
        $this->modal = false;
        $this->success('Payment method created successfully.');
    }
}
