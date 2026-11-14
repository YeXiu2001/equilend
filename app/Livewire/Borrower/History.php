<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Loan;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class History extends Component
{
    public int $limit = 12;

    public string $search = '';

    /**
     * @var array{column: string, direction: string}
     */
    public array $sort = [
        'column'    => 'borrowed_at',
        'direction' => 'desc',
    ];

    public function updatedSearch(): void
    {
        $this->limit = 12;
    }

    /**
     * Sort borrowing history by an allowed column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['id', 'borrowed_at', 'due_at', 'returned_at'], true)) {
            return;
        }

        if ($this->sort['column'] === $column) {
            $this->sort['direction'] = $this->sort['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort['column']    = $column;
            $this->sort['direction'] = 'asc';
        }

        $this->limit = 12;
    }

    /**
     * Increase loan limit for infinite scrolling.
     */
    public function loadMore(): void
    {
        $this->limit += 12;
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function loans(): Collection
    {
        return $this->baseQuery()
            ->orderBy($this->sort['column'], $this->sort['direction'])
            ->limit($this->limit)
            ->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->loans->count() < $this->totalCount;
    }

    public function render(): View
    {
        return view('livewire.borrower.history');
    }

    /**
     * Base query including both currently borrowed (unreturned) and past returned loans.
     *
     * @return Builder<Loan>
     */
    protected function baseQuery(): Builder
    {
        return Loan::query()
            ->with(['organization', 'item', 'itemUnit'])
            ->where('user_id', Auth::id())
            ->when(mb_trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->whereHas('item', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', $term));
            });
    }
}
