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

class Update extends Component
{
    use Alert;

    public ?Organization $organization = null;

    public string $name = '';

    public ?string $code = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $description = null;

    public bool $is_active = true;

    public ?int $assigned_user_id = null;

    public string $user_search = '';

    public bool $modal = false;

    public function render(): View
    {
        return view('livewire.organization.update');
    }

    #[On('load::organization')]
    public function load(Organization $organization): void
    {
        $this->organization     = $organization;
        $this->name             = $organization->name;
        $this->code             = $organization->code;
        $this->email            = $organization->email;
        $this->phone            = $organization->phone;
        $this->description      = $organization->description;
        $this->is_active        = (bool) $organization->is_active;
        $this->assigned_user_id = $organization->assignedUser?->id;
        $this->user_search      = '';

        $this->resetValidation();
        $this->modal = true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'code'             => ['nullable', 'string', 'max:20', Rule::unique('organizations', 'code')->ignore($this->organization?->id)],
            'email'            => ['nullable', 'string', 'email', 'max:255'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'is_active'        => ['boolean'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    #[Computed]
    public function selectedUser(): ?User
    {
        return $this->assigned_user_id !== null ? User::find($this->assigned_user_id) : null;
    }

    #[Computed]
    public function availableUsers(): Collection
    {
        $term              = mb_trim($this->user_search);
        $currentAssignedId = $this->assigned_user_id;

        return User::query()
            ->where(function ($query) use ($currentAssignedId): void {
                $query->whereDoesntHave('organizations');

                if ($currentAssignedId !== null) {
                    $query->orWhere('id', $currentAssignedId);
                }
            })
            ->when($term !== '', function ($query) use ($term): void {
                $query->where(function ($q) use ($term): void {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    public function selectUser(int $userId): void
    {
        $this->assigned_user_id = $userId;
        $this->user_search      = '';
    }

    public function clearAssignedUser(): void
    {
        $this->assigned_user_id = null;
        $this->user_search      = '';
    }

    public function save(): void
    {
        if ($this->organization === null) {
            return;
        }

        $validated = $this->validate();

        $this->organization->update([
            'name'        => $validated['name'],
            'code'        => $validated['code'] ? mb_strtoupper($validated['code']) : null,
            'email'       => $validated['email'],
            'phone'       => $validated['phone'],
            'description' => $validated['description'],
            'is_active'   => $validated['is_active'],
        ]);

        if ($this->assigned_user_id !== null) {
            $this->organization->members()->sync([
                $this->assigned_user_id => ['role' => 'admin'],
            ]);
        } else {
            $this->organization->members()->detach();
        }

        $this->dispatch('updated');
        $this->modal = false;
        $this->success('Organization updated successfully.');
    }
}
