<?php

declare(strict_types=1);

namespace App\Livewire\Organization;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Organization;
use App\Livewire\Traits\Alert;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class Create extends Component
{
    use Alert;

    public string $name = '';

    public ?string $code = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $description = null;

    public bool $is_active = true;

    public ?int $assigned_user_id = null;

    public bool $modal = false;

    public function render(): View
    {
        return view('livewire.organization.create');
    }

    #[On('open::create-organization')]
    public function open(): void
    {
        $this->reset(['name', 'code', 'email', 'phone', 'address', 'description', 'assigned_user_id']);
        $this->is_active = true;
        $this->resetValidation();
        $this->modal = true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'code'             => ['nullable', 'string', 'max:20', Rule::unique('organizations', 'code')],
            'email'            => ['nullable', 'string', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'address'          => ['nullable', 'string', 'max:500'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'is_active'        => ['boolean'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    #[Computed]
    public function availableUsers(): Collection
    {
        // Users who are not yet assigned to any organization
        return User::query()
            ->whereDoesntHave('organizations')
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $validated = $this->validate();

        $organization = Organization::create([
            'name'        => $validated['name'],
            'code'        => $validated['code'] ? mb_strtoupper($validated['code']) : null,
            'email'       => $validated['email'],
            'phone'       => $validated['phone'],
            'address'     => $validated['address'],
            'description' => $validated['description'],
            'is_active'   => $validated['is_active'],
        ]);

        if ($this->assigned_user_id !== null) {
            $organization->members()->sync([
                $this->assigned_user_id => ['role' => 'admin'],
            ]);
        }

        $this->dispatch('created');
        $this->modal = false;
        $this->success('Organization created successfully.');
    }
}
