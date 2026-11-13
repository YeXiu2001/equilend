<?php

declare(strict_types=1);

namespace App\Livewire\Circulation;

use App\Models\Loan;
use Livewire\Component;
use App\Enums\LoanStatus;
use App\Models\Organization;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class OverdueTracking extends Component
{
    use Alert;

    public string $search = '';

    public string $selectedOrganizationId = 'all';

    public int $limit = 10;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to overdue tracking.');
        }

        if (! $user->hasRole('superadmin')) {
            $userOrg                      = $user->organization;
            $this->selectedOrganizationId = $userOrg ? (string) $userOrg->id : 'all';
        }
    }

    public function sendReminder(int $loanId): void
    {
        $loan = Loan::with('user')->findOrFail($loanId);

        $this->success("Return reminder notification and alert sent to {$loan->user->name} ({$loan->user->email}).");
    }

    public function loadMore(): void
    {
        $this->limit += 10;
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function overdueLoans(): Collection
    {
        return $this->baseQuery()->limit($this->limit)->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    public function render(): View
    {
        return view('livewire.circulation.overdue-tracking');
    }

    protected function baseQuery(): Builder
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Loan::query()
            ->with(['organization', 'user', 'item', 'itemUnit', 'issuedBy'])
            ->where(function (Builder $sub): void {
                $sub->where('status', LoanStatus::Overdue)
                    ->orWhere(fn ($inner) => $inner->where('status', LoanStatus::Active)->whereNull('returned_at')->where('due_at', '<', now()));
            })
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when(! empty($this->search), function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $term))
                        ->orWhereHas('itemUnit', fn ($unit) => $unit->where('asset_tag', 'like', $term)->orWhere('serial_number', 'like', $term));
                });
            })
            ->orderBy('due_at');
    }

    protected function getEffectiveOrganizationId(): ?int
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            return $user->organization?->id;
        }

        if ($this->selectedOrganizationId === 'all' || empty($this->selectedOrganizationId)) {
            return null;
        }

        return (int) $this->selectedOrganizationId;
    }
}
