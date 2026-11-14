<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Item;
use Livewire\Component;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Livewire\Traits\Alert;
use Illuminate\Support\Carbon;
use App\Enums\ReservationStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BrowseEquipment extends Component
{
    use Alert;

    public int $limit = 12;

    public string $search = '';

    // Applied Filters
    public string $selectedOrganizationId = 'all';

    public string $categoryId = 'all';

    public string $availabilityFilter = 'all';

    // Filter Modal state & pending values
    public bool $filterModal = false;

    public string $filterOrganizationId = 'all';

    public string $filterCategoryId = 'all';

    public string $filterAvailability = 'all';

    public string $viewMode = 'table';

    /**
     * @var array{column: string, direction: string}
     */
    public array $sort = [
        'column'    => 'name',
        'direction' => 'asc',
    ];

    // View Specs Modal
    public bool $specsModal = false;

    public ?int $viewingItemId = null;

    // Request Booking Modal
    public bool $bookingModal = false;

    public ?int $bookingItemId = null;

    public string $bookingStartTime = '';

    public string $bookingEndTime = '';

    public string $bookingPurpose = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user?->organization) {
            $this->selectedOrganizationId = (string) $user->organization->id;
            $this->filterOrganizationId   = $this->selectedOrganizationId;
        }
    }

    public function updatedSearch(): void
    {
        $this->limit = 12;
    }

    public function updatedFilterOrganizationId(): void
    {
        $this->filterCategoryId = 'all';
    }

    /**
     * Sort the equipment catalog by a column.
     */
    public function sortBy(string $column): void
    {
        if (! in_array($column, ['name', 'created_at'], true)) {
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
     * Increase item limit for infinite scrolling.
     */
    public function loadMore(): void
    {
        $this->limit += 12;
    }

    /**
     * Open Filter Modal synced with active filters.
     */
    public function openFilterModal(): void
    {
        $this->filterOrganizationId = $this->selectedOrganizationId;
        $this->filterCategoryId     = $this->categoryId;
        $this->filterAvailability   = $this->availabilityFilter;
        $this->filterModal          = true;
    }

    /**
     * Apply pending modal filters to the catalog.
     */
    public function applyFilters(): void
    {
        $this->selectedOrganizationId = $this->filterOrganizationId;
        $this->categoryId             = $this->filterCategoryId;
        $this->availabilityFilter     = $this->filterAvailability;
        $this->limit                  = 12;
        $this->filterModal            = false;
    }

    /**
     * Reset all filters back to default.
     */
    public function resetFilters(): void
    {
        $this->selectedOrganizationId = 'all';
        $this->filterOrganizationId   = 'all';
        $this->categoryId             = 'all';
        $this->filterCategoryId       = 'all';
        $this->availabilityFilter     = 'all';
        $this->filterAvailability     = 'all';
        $this->limit                  = 12;
        $this->filterModal            = false;
    }

    /**
     * Count active filters for UI badge/indicator.
     */
    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;

        if ($this->selectedOrganizationId !== 'all') {
            $count++;
        }

        if ($this->categoryId !== 'all') {
            $count++;
        }

        if ($this->availabilityFilter !== 'all') {
            $count++;
        }

        return $count;
    }

    public function openSpecsModal(int $itemId): void
    {
        $this->viewingItemId = $itemId;
        $this->specsModal    = true;
    }

    public function openBookingModal(int $itemId): void
    {
        $user = Auth::user();

        if (! $user || ! $user->canBorrow()) {
            $reasons = $user ? implode(' ', $user->getBorrowingBlockReasons()) : 'Unauthorized.';
            $this->error("Borrowing suspended: {$reasons}");

            return;
        }

        $item = Item::where('is_active', true)->findOrFail($itemId);

        if (Reservation::hasActiveForUserAndItem($user->id, $item->id)) {
            $this->error('You already have an active reservation for this equipment. Only one reservation per equipment is allowed.');

            return;
        }

        $this->bookingItemId    = $item->id;
        $this->bookingStartTime = now()->addDay()->setTime(9, 0)->format('Y-m-d\TH:i');
        $this->bookingEndTime   = now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
        $this->bookingPurpose   = '';
        $this->bookingModal     = true;
    }

    public function submitBooking(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->canBorrow()) {
            $reasons = $user ? implode(' ', $user->getBorrowingBlockReasons()) : 'Unauthorized.';
            $this->error("Reservation blocked: {$reasons}");
            $this->bookingModal = false;

            return;
        }

        $this->validate([
            'bookingItemId'    => ['required', 'exists:items,id'],
            'bookingStartTime' => ['required', 'date', 'after:now'],
            'bookingEndTime'   => ['required', 'date', 'after:bookingStartTime'],
            'bookingPurpose'   => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'bookingStartTime.after'  => 'Pickup time must be in the future.',
            'bookingEndTime.after'    => 'Return time must be after pickup time.',
            'bookingPurpose.required' => 'Please state your academic/lab purpose.',
        ]);

        $item = Item::where('is_active', true)->findOrFail($this->bookingItemId);

        if (Reservation::hasActiveForUserAndItem($user->id, $item->id)) {
            $this->error('You already have an active reservation for this equipment. Only one reservation per equipment is allowed.');
            $this->bookingModal = false;

            return;
        }

        $reservation = Reservation::create([
            'organization_id' => $item->organization_id,
            'user_id'         => $user->id,
            'item_id'         => $item->id,
            'start_time'      => Carbon::parse($this->bookingStartTime),
            'end_time'        => Carbon::parse($this->bookingEndTime),
            'purpose'         => mb_trim($this->bookingPurpose),
            'status'          => ReservationStatus::Pending,
        ]);

        $this->bookingModal = false;
        $this->reset(['bookingItemId', 'bookingPurpose']);
        unset($this->activeReservedItemIds);
        $this->success("Booking request #{$reservation->id} for {$item->name} submitted for staff approval.");
    }

    /**
     * Equipment IDs for which the authenticated borrower already has a pending or approved reservation.
     *
     * @return list<int>
     */
    #[Computed]
    public function activeReservedItemIds(): array
    {
        $userId = Auth::id();

        if (! $userId) {
            return [];
        }

        return Reservation::query()
            ->where('user_id', $userId)
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved])
            ->pluck('item_id')
            ->unique()
            ->values()
            ->all();
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

    #[Computed]
    public function bookingItem(): ?Item
    {
        if (! $this->bookingItemId) {
            return null;
        }

        return Item::with(['organization', 'category'])->find($this->bookingItemId);
    }

    /**
     * @return Collection<int, Organization>
     */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::active()->orderBy('code')->get();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        $orgId = $this->filterModal ? $this->filterOrganizationId : $this->selectedOrganizationId;

        return Category::query()
            ->when(
                $orgId !== 'all' && ! empty($orgId),
                fn (Builder $q) => $q->where('organization_id', (int) $orgId)
            )
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * @return Collection<int, Item>
     */
    #[Computed]
    public function items(): Collection
    {
        return $this->baseQuery()
            ->orderBy($this->sort['column'], $this->sort['direction'])
            ->limit($this->limit)
            ->get();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->items->count() < $this->totalCount;
    }

    public function render(): View
    {
        return view('livewire.borrower.browse-equipment');
    }

    /**
     * Base query scoped to active items, equipment name search, and applied filters.
     *
     * @return Builder<Item>
     */
    protected function baseQuery(): Builder
    {
        return Item::query()
            ->with(['organization', 'category'])
            ->withCount([
                'units',
                'units as available_units_count' => fn (Builder $q) => $q->where('status', ItemUnitStatus::Available),
            ])
            ->where('is_active', true)
            ->when(
                $this->selectedOrganizationId !== 'all' && ! empty($this->selectedOrganizationId),
                fn (Builder $q) => $q->where('organization_id', (int) $this->selectedOrganizationId)
            )
            ->when(
                $this->categoryId !== 'all' && ! empty($this->categoryId),
                fn (Builder $q) => $q->where('category_id', (int) $this->categoryId)
            )
            ->when($this->availabilityFilter === 'available_only', function (Builder $q): void {
                $q->whereHas('units', fn (Builder $u) => $u->where('status', ItemUnitStatus::Available));
            })
            ->when(mb_trim($this->search) !== '', function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->where('name', 'like', $term);
            });
    }
}
