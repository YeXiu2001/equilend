<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use Livewire\Component;
use App\Models\Reservation;
use App\Livewire\Traits\Alert;
use App\Enums\ReservationStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Reservations extends Component
{
    use Alert;

    public int $limit = 10;

    public string $search = '';

    // Applied Filter
    public string $statusFilter = 'all';

    // Filter Modal state & pending value
    public bool $filterModal = false;

    public string $filterStatus = 'all';

    // Cancel Caution Modal state
    public bool $cancelModal = false;

    public ?int $cancellingReservationId = null;

    /**
     * @var array{column: string, direction: string}
     */
    public array $sort = [
        'column'    => 'created_at',
        'direction' => 'desc',
    ];

    public function updatedSearch(): void
    {
        $this->limit = 10;
    }

    public function updatedStatusFilter(): void
    {
        $this->limit = 10;
    }

    /**
     * Sort reservations by an allowed column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['id', 'start_time', 'created_at'], true)) {
            return;
        }

        if ($this->sort['column'] === $column) {
            $this->sort['direction'] = $this->sort['direction'] === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort['column']    = $column;
            $this->sort['direction'] = 'asc';
        }

        $this->limit = 10;
    }

    /**
     * Increase reservation limit for infinite scrolling.
     */
    public function loadMore(): void
    {
        $this->limit += 10;
    }

    /**
     * Open Filter Modal synced with active filter.
     */
    public function openFilterModal(): void
    {
        $this->filterStatus = $this->statusFilter;
        $this->filterModal  = true;
    }

    /**
     * Apply pending modal filters.
     */
    public function applyFilters(): void
    {
        $this->statusFilter = $this->filterStatus;
        $this->limit        = 10;
        $this->filterModal  = false;
    }

    /**
     * Reset all filters back to default.
     */
    public function resetFilters(): void
    {
        $this->statusFilter = 'all';
        $this->filterStatus = 'all';
        $this->limit        = 10;
        $this->filterModal  = false;
    }

    /**
     * Count active filters for UI badge/indicator.
     */
    #[Computed]
    public function activeFilterCount(): int
    {
        return $this->statusFilter !== 'all' ? 1 : 0;
    }

    public function openCancelModal(int $reservationId): void
    {
        $reservation = Reservation::where('user_id', Auth::id())->findOrFail($reservationId);

        if (! in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Approved], true)) {
            $this->error('Only pending or approved reservations can be cancelled.');

            return;
        }

        $this->cancellingReservationId = $reservation->id;
        $this->cancelModal             = true;
    }

    public function confirmCancelReservation(): void
    {
        if (! $this->cancellingReservationId) {
            return;
        }

        $this->cancelReservation($this->cancellingReservationId);
        $this->cancelModal             = false;
        $this->cancellingReservationId = null;
    }

    #[Computed]
    public function cancellingReservation(): ?Reservation
    {
        if (! $this->cancellingReservationId) {
            return null;
        }

        return Reservation::with(['item', 'itemUnit'])
            ->where('user_id', Auth::id())
            ->find($this->cancellingReservationId);
    }

    public function cancelReservation(int $reservationId): void
    {
        $reservation = Reservation::with('itemUnit')->where('user_id', Auth::id())->findOrFail($reservationId);

        if (! in_array($reservation->status, [ReservationStatus::Pending, ReservationStatus::Approved], true)) {
            $this->error('Only pending or approved reservations can be cancelled.');

            return;
        }

        $reservation->cancel();

        $this->success("Reservation #{$reservation->id} has been cancelled.");
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @return Collection<int, Reservation>
     */
    #[Computed]
    public function reservations(): Collection
    {
        return $this->baseQuery()
            ->orderBy($this->sort['column'], $this->sort['direction'])
            ->limit($this->limit)
            ->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->reservations->count() < $this->totalCount;
    }

    public function render(): View
    {
        return view('livewire.borrower.reservations');
    }

    /**
     * Base query scoped to the authenticated borrower, equipment name search, and status filter.
     *
     * @return Builder<Reservation>
     */
    protected function baseQuery(): Builder
    {
        return Reservation::query()
            ->with(['organization', 'item', 'itemUnit'])
            ->where('user_id', Auth::id())
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when(mb_trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->whereHas('item', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', $term));
            });
    }
}
