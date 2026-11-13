<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Loan;
use Livewire\Component;
use App\Enums\LoanStatus;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class History extends Component
{
    use WithPagination;

    #[Computed]
    public function pastLoans(): LengthAwarePaginator
    {
        return Loan::query()
            ->with(['organization', 'item', 'itemUnit'])
            ->where('user_id', Auth::id())
            ->where(function ($query): void {
                $query->whereNotNull('returned_at')
                    ->orWhereIn('status', [LoanStatus::Returned, LoanStatus::Damaged, LoanStatus::Lost]);
            })
            ->latest('returned_at')
            ->paginate(12);
    }

    #[Computed]
    public function metrics(): array
    {
        $completed = Loan::query()
            ->where('user_id', Auth::id())
            ->whereNotNull('returned_at')
            ->get(['due_at', 'returned_at']);

        $total  = $completed->count();
        $onTime = $completed->filter(fn (Loan $loan): bool => $loan->returned_at <= $loan->due_at)->count();
        $rate   = $total > 0 ? round(($onTime / $total) * 100, 1) : 100.0;

        return [
            'total'   => $total,
            'on_time' => $onTime,
            'late'    => $total - $onTime,
            'rate'    => $rate,
        ];
    }

    public function render(): View
    {
        return view('livewire.borrower.history');
    }
}
