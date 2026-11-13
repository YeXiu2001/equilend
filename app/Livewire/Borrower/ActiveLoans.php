<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Loan;
use Livewire\Component;
use App\Enums\LoanStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class ActiveLoans extends Component
{
    #[Computed]
    public function activeLoans(): Collection
    {
        return Loan::query()
            ->with(['organization', 'item', 'itemUnit', 'issuedBy'])
            ->where('user_id', Auth::id())
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->whereNull('returned_at')
            ->orderBy('due_at')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.borrower.active-loans');
    }
}
