<?php

declare(strict_types=1);

namespace App\Livewire\PaymentMethods;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\PaymentMethod;
use App\Livewire\Traits\Alert;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\View\View;

class Update extends Component
{
    use Alert;

    public ?PaymentMethod $paymentMethod = null;

    public string $payment_code = '';

    public string $name = '';

    public ?string $description = null;

    public bool $is_active = true;

    public bool $modal = false;

    public function render(): View
    {
        return view('livewire.payment-methods.update');
    }

    #[On('load::payment-method')]
    public function load(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
        $this->payment_code  = $paymentMethod->payment_code;
        $this->name          = $paymentMethod->name;
        $this->description   = $paymentMethod->description;
        $this->is_active     = (bool) $paymentMethod->is_active;

        $this->resetValidation();
        $this->modal = true;
    }

    public function rules(): array
    {
        return [
            'payment_code' => ['required', 'string', 'max:50', Rule::unique('payment_methods', 'payment_code')->ignore($this->paymentMethod?->id)],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:500'],
            'is_active'    => ['boolean'],
        ];
    }

    public function save(): void
    {
        if ($this->paymentMethod === null) {
            return;
        }

        $validated = $this->validate();

        $this->paymentMethod->update([
            'payment_code' => mb_strtolower($validated['payment_code']),
            'name'         => $validated['name'],
            'description'  => $validated['description'],
            'is_active'    => $validated['is_active'],
        ]);

        $this->dispatch('updated');
        $this->modal = false;
        $this->success('Payment method updated successfully.');
    }
}
