<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Item;
use App\Models\Loan;
use Livewire\Component;
use App\Enums\LoanStatus;
use App\Enums\ItemUnitStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ActiveLoans extends Component
{
    public string $search = '';

    // View Specs Modal
    public bool $specsModal = false;

    public ?int $viewingItemId = null;

    public function openSpecsModal(int $itemId): void
    {
        $this->viewingItemId = $itemId;
        $this->specsModal    = true;
    }

    #[Computed]
    public function viewingItem(): ?Item
    {
        if (! $this->viewingItemId) {
            return null;
        }

        return Item::with(['organization', 'category'])
            ->withCount([
                'units',
                'units as available_units_count' => fn (Builder $q) => $q->where('status', ItemUnitStatus::Available),
            ])
            ->find($this->viewingItemId);
    }

    /**
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function activeLoans(): Collection
    {
        return Loan::query()
            ->with(['organization', 'item.category', 'itemUnit', 'issuedBy'])
            ->where('user_id', Auth::id())
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->whereNull('returned_at')
            ->when(mb_trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->whereHas('item', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', $term));
            })
            ->orderBy('due_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.borrower.active-loans');
    }
}
