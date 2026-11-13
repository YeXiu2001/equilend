<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Item;
use Livewire\Component;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\Organization;
use Livewire\WithPagination;
use App\Enums\ItemUnitStatus;
use App\Livewire\Traits\Alert;
use Illuminate\Support\Carbon;
use App\Enums\ReservationStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BrowseEquipment extends Component
{
    use Alert;
    use WithPagination;

    public string $search = '';

    public string $selectedOrganizationId = 'all';

    public string $categoryId = 'all';

    public string $availabilityFilter = 'all';

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
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedOrganizationId(): void
    {
        $this->categoryId = 'all';
        $this->resetPage();
    }

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedAvailabilityFilter(): void
    {
        $this->resetPage();
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
        $this->success("Booking request #{$reservation->id} for {$item->name} submitted for staff approval.");
    }

    #[Computed]
    public function viewingItem(): ?Item
    {
        if (! $this->viewingItemId) {
            return null;
        }

        return Item::with(['organization', 'category'])
            ->withCount([
                'units as available_units_count' => fn (Builder $q) => $q->where('status', ItemUnitStatus::Available),
            ])
            ->find($this->viewingItemId);
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::active()->orderBy('code')->get();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->when(
                $this->selectedOrganizationId !== 'all' && ! empty($this->selectedOrganizationId),
                fn (Builder $q) => $q->where('organization_id', (int) $this->selectedOrganizationId)
            )
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        return Item::query()
            ->with(['organization', 'category'])
            ->withCount([
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
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('name', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('specifications', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.borrower.browse-equipment');
    }
}
