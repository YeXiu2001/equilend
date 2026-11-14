<?php

declare(strict_types=1);

namespace App\Livewire\Circulation;

use Livewire\Component;
use App\Models\ItemUnit;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use InvalidArgumentException;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\ReservationStatusNotification;

class BookingRequests extends Component
{
    use Alert;

    public string $search = '';

    public string $statusFilter = 'pending';

    public string $selectedOrganizationId = 'all';

    public int $limit = 10;

    // Approve Modal
    public bool $approveModal = false;

    public ?int $approvingReservationId = null;

    public ?int $assignedUnitId = null;

    // Reject Modal
    public bool $rejectModal = false;

    public ?int $rejectingReservationId = null;

    public string $rejectionRemarks = '';

    // Cancel Modal
    public bool $cancelModal = false;

    public ?int $cancellingReservationId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to booking requests.');
        }

        if (! $user->hasRole('superadmin')) {
            $userOrg                      = $user->organization;
            $this->selectedOrganizationId = $userOrg ? (string) $userOrg->id : 'all';
        }
    }

    public function updatedSearch(): void
    {
        $this->limit = 10;
    }

    public function updatedStatusFilter(): void
    {
        $this->limit = 10;
    }

    public function updatedSelectedOrganizationId(): void
    {
        $this->limit = 10;
    }

    public function loadMore(): void
    {
        $this->limit += 10;
    }

    public function openApproveModal(int $id): void
    {
        $reservation = $this->findAccessibleReservation($id);

        if (! $reservation->isPending()) {
            $this->error('Only pending reservations can be approved.');

            return;
        }

        $this->approvingReservationId = $reservation->id;
        $this->assignedUnitId         = $reservation->item_unit_id;
        $this->resetValidation('assignedUnitId');
        $this->approveModal = true;
    }

    public function confirmApproval(): void
    {
        if (! $this->approvingReservationId) {
            return;
        }

        $this->validate([
            'assignedUnitId' => ['required', 'integer', 'exists:item_units,id'],
        ], [
            'assignedUnitId.required' => 'Please select a physical equipment unit to approve this reservation.',
            'assignedUnitId.exists'   => 'The selected physical equipment unit could not be found.',
        ]);

        $reservation = $this->findAccessibleReservation($this->approvingReservationId);

        $unit = ItemUnit::query()
            ->where('item_id', $reservation->item_id)
            ->where('organization_id', $reservation->organization_id)
            ->find($this->assignedUnitId);

        if (! $unit) {
            $this->error('Selected unit does not belong to this equipment item or organization.');

            return;
        }

        if ($unit->status !== ItemUnitStatus::Available && $unit->status !== ItemUnitStatus::Reserved) {
            $this->error("Selected unit {$unit->asset_tag} is currently {$unit->status->label()} and cannot be assigned.");

            return;
        }

        $reservation->approve(Auth::user(), $unit);
        $reservation->user?->notify(new ReservationStatusNotification($reservation));

        $this->success("Reservation #{$reservation->id} for {$reservation->user->name} has been approved (Unit {$unit->asset_tag} allocated).");

        $this->approveModal           = false;
        $this->approvingReservationId = null;
        $this->assignedUnitId         = null;
    }

    public function openRejectModal(int $id): void
    {
        $reservation = $this->findAccessibleReservation($id);

        if (! $reservation->isPending()) {
            $this->error('Only pending reservations can be rejected.');

            return;
        }

        $this->rejectingReservationId = $reservation->id;
        $this->rejectionRemarks       = '';
        $this->rejectModal            = true;
    }

    public function confirmRejection(): void
    {
        if (! $this->rejectingReservationId) {
            return;
        }

        $this->validate([
            'rejectionRemarks' => ['required', 'string', 'min:5'],
        ], [
            'rejectionRemarks.required' => 'Rejection remarks are mandatory to explain why the booking was declined.',
            'rejectionRemarks.min'      => 'Rejection remarks must contain at least 5 characters.',
        ]);

        $reservation = $this->findAccessibleReservation($this->rejectingReservationId);

        try {
            $reservation->reject(Auth::user(), $this->rejectionRemarks);
            $reservation->user?->notify(new ReservationStatusNotification($reservation));
            $this->success("Reservation #{$reservation->id} for {$reservation->user->name} was rejected.");

            $this->rejectModal            = false;
            $this->rejectingReservationId = null;
            $this->rejectionRemarks       = '';
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
    }

    public function openCancelModal(int $id): void
    {
        $reservation = $this->findAccessibleReservation($id);

        if (! $reservation->isApproved()) {
            $this->error('Only approved reservations can be cancelled.');

            return;
        }

        $this->cancellingReservationId = $reservation->id;
        $this->cancelModal             = true;
    }

    public function confirmCancellation(): void
    {
        if (! $this->cancellingReservationId) {
            return;
        }

        $reservation = $this->findAccessibleReservation($this->cancellingReservationId);

        if (! $reservation->isApproved()) {
            $this->error('Only approved reservations can be cancelled.');
            $this->cancelModal             = false;
            $this->cancellingReservationId = null;

            return;
        }

        $unitTag = $reservation->itemUnit?->asset_tag;
        $reservation->cancel();

        $unitMsg = $unitTag ? " Unit {$unitTag} is now available again." : '';
        $this->success("Reservation #{$reservation->id} for {$reservation->user->name} has been cancelled.{$unitMsg}");

        $this->cancelModal             = false;
        $this->cancellingReservationId = null;
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function availableUnitsForApproving(): Collection
    {
        if (! $this->approvingReservationId) {
            return new Collection();
        }

        $reservation = Reservation::find($this->approvingReservationId);

        if (! $reservation) {
            return new Collection();
        }

        return ItemUnit::query()
            ->where('item_id', $reservation->item_id)
            ->where('organization_id', $reservation->organization_id)
            ->whereIn('status', [ItemUnitStatus::Available, ItemUnitStatus::Reserved])
            ->orderBy('asset_tag')
            ->get();
    }

    #[Computed]
    public function reservations(): Collection
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
        return view('livewire.circulation.booking-requests');
    }

    protected function baseQuery(): Builder
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Reservation::query()
            ->with(['organization', 'user', 'item', 'itemUnit', 'approvedBy'])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->statusFilter !== 'all', function (Builder $q): void {
                $q->where('status', $this->statusFilter);
            })
            ->when(! empty($this->search), function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $term))
                        ->orWhere('purpose', 'like', $term);
                });
            })
            ->latest('created_at');
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

    protected function findAccessibleReservation(int $id): Reservation
    {
        $res  = Reservation::with(['organization', 'user', 'item', 'itemUnit'])->findOrFail($id);
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            $userOrg = $user->organization;

            if ($userOrg && $res->organization_id !== $userOrg->id) {
                abort(403, 'You are not authorized to manage reservations for another college.');
            }
        }

        return $res;
    }
}
