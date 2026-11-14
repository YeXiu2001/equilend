<?php

declare(strict_types=1);

namespace App\Livewire\Circulation;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use DomainException;
use App\Models\Penalty;
use Livewire\Component;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Enums\PenaltyType;
use App\Models\Reservation;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Livewire\Traits\Alert;
use Illuminate\Support\Carbon;
use App\Enums\ItemUnitCondition;
use App\Enums\ReservationStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\PenaltyImposedNotification;

class Desk extends Component
{
    use Alert;

    public string $mode = 'checkout'; // 'checkout' or 'checkin'

    public string $selectedOrganizationId = 'all';

    // CHECK-OUT FORM STATE
    public string $checkoutSource = 'walkin'; // 'walkin' or 'reservation'

    public bool $reservationModal = false;

    public string $reservationSearch = '';

    public ?int $selectedUserId = null;

    public string $studentSearch = '';

    public ?int $selectedReservationId = null;

    public ?int $selectedItemId = null;

    public string $itemSearch = '';

    public ?int $selectedUnitId = null;

    public string $unitSearch = '';

    public string $dueAt = '';

    public string $checkoutCondition = 'good';

    public string $checkoutNotes = '';

    // CHECK-IN STATE
    public string $checkinSearch = '';

    public ?int $inspectingLoanId = null;

    public string $checkinCondition = 'good';

    public string $checkinNotes = '';

    public bool $isDamaged = false;

    public bool $assessPenalty = false;

    public string $penaltyType = 'damage';

    public string $penaltyAmount = '';

    public string $penaltyNotes = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to circulation desk operations.');
        }

        if (! $user->hasRole('superadmin')) {
            $userOrg                      = $user->organization;
            $this->selectedOrganizationId = $userOrg ? (string) $userOrg->id : 'all';
        }

        // Default due date: tomorrow at 5:00 PM
        $this->dueAt = now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
    }

    public function setMode(string $mode): void
    {
        $this->mode = in_array($mode, ['checkout', 'checkin'], true) ? $mode : 'checkout';
    }

    public function setCheckoutSource(string $source): void
    {
        $this->checkoutSource = in_array($source, ['walkin', 'reservation'], true) ? $source : 'walkin';

        if ($this->checkoutSource === 'walkin') {
            $this->selectedReservationId = null;
            $this->reservationModal      = false;
        } elseif (! $this->selectedReservationId) {
            $this->reservationModal = true;
        }
    }

    public function openReservationModal(): void
    {
        $this->reservationModal = true;
    }

    public function clearSelectedStudent(): void
    {
        $this->selectedUserId = null;
        $this->studentSearch  = '';
    }

    public function updatedSelectedItemId(): void
    {
        $this->selectedUnitId = null;
        $this->unitSearch     = '';
    }

    public function selectItem(int $itemId): void
    {
        $orgId = $this->getEffectiveOrganizationId();
        $item  = Item::query()
            ->where('is_active', true)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->findOrFail($itemId);

        $this->selectedItemId = $item->id;
        $this->itemSearch     = $item->name;
        $this->selectedUnitId = null;
        $this->unitSearch     = '';
    }

    public function clearSelectedItem(): void
    {
        $this->selectedItemId = null;
        $this->itemSearch     = '';
        $this->selectedUnitId = null;
        $this->unitSearch     = '';
    }

    public function selectUnit(int $unitId): void
    {
        $orgId = $this->getEffectiveOrganizationId();
        $unit  = ItemUnit::query()
            ->with('item')
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->findOrFail($unitId);

        if ($unit->status !== ItemUnitStatus::Available && $unit->status !== ItemUnitStatus::Reserved) {
            $this->error("Unit {$unit->asset_tag} is currently {$unit->status->label()} and cannot be checked out.");

            return;
        }

        $this->selectedItemId    = $unit->item_id;
        $this->itemSearch        = $unit->item->name ?? '';
        $this->selectedUnitId    = $unit->id;
        $this->unitSearch        = $unit->asset_tag;
        $this->checkoutCondition = $unit->condition->value;
    }

    public function clearSelectedUnit(): void
    {
        $this->selectedUnitId = null;
        $this->unitSearch     = '';
    }

    public function updatedUnitSearch(): void
    {
        if (empty(mb_trim($this->unitSearch))) {
            return;
        }

        // Quick barcode / asset tag scanner matching
        $tag   = mb_trim($this->unitSearch);
        $orgId = $this->getEffectiveOrganizationId();

        $unit = ItemUnit::query()
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->where(function (Builder $q) use ($tag): void {
                $q->where('asset_tag', $tag)
                    ->orWhere('serial_number', $tag);
            })
            ->first();

        if ($unit) {
            if ($unit->status !== ItemUnitStatus::Available && $unit->status !== ItemUnitStatus::Reserved) {
                $this->error("Unit {$unit->asset_tag} is currently {$unit->status->label()} and cannot be checked out.");
            } else {
                $this->selectedItemId    = $unit->item_id;
                $this->itemSearch        = $unit->item->name ?? '';
                $this->selectedUnitId    = $unit->id;
                $this->unitSearch        = $unit->asset_tag;
                $this->checkoutCondition = $unit->condition->value;
                $this->info("Unit {$unit->asset_tag} ({$unit->item->name}) selected.");
            }
        }
    }

    public function selectStudent(int $userId): void
    {
        $this->selectedUserId = $userId;
        $student              = User::find($userId);

        if ($student) {
            $this->studentSearch = $student->name;
        }

        // Check for any approved reservations for this student
        $orgId = $this->getEffectiveOrganizationId();

        $approvedRes = Reservation::query()
            ->where('user_id', $userId)
            ->where('status', ReservationStatus::Approved)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->first();

        if ($approvedRes) {
            $this->selectedReservationId = $approvedRes->id;
            $this->selectedItemId        = $approvedRes->item_id;
            $this->itemSearch            = $approvedRes->item?->name ?? '';

            if ($approvedRes->item_unit_id) {
                $this->selectedUnitId = $approvedRes->item_unit_id;
                $this->unitSearch     = $approvedRes->itemUnit?->asset_tag ?? '';
            }

            $this->info("Found approved reservation #{$approvedRes->id} for {$approvedRes->item->name}. Form pre-populated.");
        }
    }

    public function applyReservation(int $reservationId): void
    {
        $res = Reservation::with(['user', 'item', 'itemUnit'])->findOrFail($reservationId);

        $this->checkoutSource        = 'reservation';
        $this->selectedReservationId = $res->id;
        $this->selectedUserId        = $res->user_id;
        $this->studentSearch         = $res->user->name;
        $this->selectedItemId        = $res->item_id;
        $this->itemSearch            = $res->item?->name ?? '';
        $this->reservationModal      = false;

        if ($res->item_unit_id) {
            $this->selectedUnitId    = $res->item_unit_id;
            $this->unitSearch        = $res->itemUnit?->asset_tag ?? '';
            $this->checkoutCondition = $res->itemUnit?->condition->value ?? 'good';
        }

        if ($res->end_time) {
            $this->dueAt = $res->end_time->format('Y-m-d\TH:i');
        }

        $this->info("Reservation #{$res->id} loaded for checkout.");
    }

    public function clearReservation(): void
    {
        $this->selectedReservationId = null;
        $this->checkoutSource        = 'walkin';
    }

    public function setDueDays(int $days): void
    {
        $this->dueAt = now()->addDays($days)->setTime(17, 0)->format('Y-m-d\TH:i');
    }

    public function processCheckout(): void
    {
        $this->validate([
            'selectedUserId'    => ['required', 'exists:users,id'],
            'selectedUnitId'    => ['required', 'exists:item_units,id'],
            'dueAt'             => ['required', 'date'],
            'checkoutCondition' => ['required', 'in:new,good,fair,poor'],
        ], [
            'selectedUserId.required' => 'Please select a borrower/student.',
            'selectedUnitId.required' => 'Please select an available physical equipment unit.',
            'dueAt.required'          => 'Return deadline date and time is required.',
        ]);

        $unit     = ItemUnit::findOrFail($this->selectedUnitId);
        $borrower = User::findOrFail($this->selectedUserId);
        $user     = Auth::user();

        if (! $borrower->canBorrow()) {
            $this->error('Borrowing suspended for '.$borrower->name.': '.implode(' ', $borrower->getBorrowingBlockReasons()));

            return;
        }

        // Check if admin is scoped to another college
        if (! $user->hasRole('superadmin')) {
            $userOrg = $user->organization;

            if ($userOrg && $unit->organization_id !== $userOrg->id) {
                $this->error('You cannot issue equipment belonging to another college.');

                return;
            }
        }

        $dueDateTime = Carbon::parse($this->dueAt);

        if ($dueDateTime->isPast()) {
            $this->error('Return deadline must be set to a future date and time.');

            return;
        }

        try {
            $conditionEnum = ItemUnitCondition::from($this->checkoutCondition);

            $loan = Loan::checkOut([
                'organization_id'    => $unit->organization_id,
                'reservation_id'     => $this->selectedReservationId,
                'user_id'            => $this->selectedUserId,
                'item_id'            => $unit->item_id,
                'item_unit_id'       => $unit->id,
                'issued_by'          => $user->id,
                'due_at'             => $dueDateTime,
                'checkout_condition' => $conditionEnum,
                'notes'              => ! empty($this->checkoutNotes) ? mb_trim($this->checkoutNotes) : null,
            ]);

            $this->success("Check-out successful! Loan #{$loan->id} recorded for {$loan->user->name}. Unit {$unit->asset_tag} is now borrowed.");

            // Reset checkout form
            $this->reset([
                'selectedUserId',
                'studentSearch',
                'selectedReservationId',
                'reservationSearch',
                'selectedItemId',
                'itemSearch',
                'selectedUnitId',
                'unitSearch',
                'checkoutNotes',
            ]);
            $this->checkoutSource    = 'walkin';
            $this->dueAt             = now()->addDay()->setTime(17, 0)->format('Y-m-d\TH:i');
            $this->checkoutCondition = 'good';
        } catch (DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    public function inspectLoan(int $loanId): void
    {
        $loan = $this->findAccessibleLoan($loanId);

        $this->inspectingLoanId = $loan->id;
        $this->checkinCondition = $loan->checkout_condition->value;
        $this->checkinNotes     = '';
        $this->isDamaged        = false;
        $this->assessPenalty    = $loan->isOverdue();
        $this->penaltyType      = $loan->isOverdue() ? 'overdue' : 'damage';
        $this->penaltyAmount    = $loan->isOverdue()
            ? (string) max(100, (int) ceil($loan->due_at->diffInHours(now()) / 24) * 50)
            : '';
        $this->penaltyNotes = '';
    }

    public function updatedIsDamaged(bool $val): void
    {
        if ($val) {
            $this->checkinCondition = 'poor';
            $this->penaltyType      = 'damage';
        } else {
            $this->checkinCondition = 'good';
        }
    }

    public function processCheckin(): void
    {
        if (! $this->inspectingLoanId) {
            $this->error('No active loan selected for return check-in.');

            return;
        }

        $loan = $this->findAccessibleLoan($this->inspectingLoanId);

        if ($loan->returned_at !== null) {
            $this->error('This loan has already been marked as returned.');
            $this->inspectingLoanId = null;

            return;
        }

        if ($this->assessPenalty) {
            $this->validate([
                'penaltyType'   => ['required', 'in:overdue,damage,loss,other'],
                'penaltyAmount' => ['required', 'numeric', 'min:1'],
                'penaltyNotes'  => ['required', 'string', 'min:3'],
            ], [
                'penaltyAmount.required' => 'Please enter a penalty amount.',
                'penaltyNotes.required'  => 'Please provide assessment remarks for the penalty.',
            ]);
        }

        $user          = Auth::user();
        $conditionEnum = $this->isDamaged
            ? ItemUnitCondition::Poor
            : ItemUnitCondition::from($this->checkinCondition);

        $loan->markAsReturned(
            $user->id,
            $conditionEnum,
            ! empty($this->checkinNotes) ? mb_trim($this->checkinNotes) : null
        );

        $penaltyCreatedMsg = '';

        if ($this->assessPenalty) {
            $amount  = round((float) $this->penaltyAmount, 2);
            $penalty = Penalty::create([
                'organization_id'   => $loan->organization_id,
                'loan_id'           => $loan->id,
                'user_id'           => $loan->user_id,
                'item_unit_id'      => $loan->item_unit_id,
                'type'              => PenaltyType::from($this->penaltyType),
                'amount'            => $amount,
                'paid_amount'       => 0.00,
                'balance_remaining' => $amount,
                'status'            => PenaltyStatus::Unpaid,
                'assessment_notes'  => mb_trim($this->penaltyNotes),
                'assessed_by'       => $user->id,
            ]);

            $loan->user?->notify(new PenaltyImposedNotification($penalty));

            $penaltyCreatedMsg = ' Penalty #'.$penalty->id.' (₱'.number_format($amount, 2).') assessed.';
        }

        $statusLabel = $conditionEnum === ItemUnitCondition::Poor ? 'Damaged (Sent for Assessment)' : 'Available';

        $this->success("Check-in completed! Loan #{$loan->id} returned by {$loan->user->name}. Physical unit {$loan->itemUnit->asset_tag} status updated to {$statusLabel}.{$penaltyCreatedMsg}");

        $this->inspectingLoanId = null;
        $this->checkinSearch    = '';
        $this->checkinNotes     = '';
        $this->isDamaged        = false;
        $this->assessPenalty    = false;
        $this->penaltyAmount    = '';
        $this->penaltyNotes     = '';
    }

    #[Computed]
    public function selectedBorrower(): ?User
    {
        if (! $this->selectedUserId) {
            return null;
        }

        return User::with('organizations')->find($this->selectedUserId);
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function students(): Collection
    {
        $search = mb_trim($this->studentSearch);

        if (mb_strlen($search) < 2) {
            return new Collection();
        }

        $hasMemberRole = \Spatie\Permission\Models\Role::where('name', 'member')->where('guard_name', 'web')->exists();
        $term          = '%'.$search.'%';

        return User::query()
            ->with('organizations')
            ->when($hasMemberRole, fn (Builder $q) => $q->role('member'))
            ->where(fn (Builder $sub) => $sub->where('name', 'like', $term)->orWhere('email', 'like', $term))
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function approvedReservationsCount(): int
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Reservation::query()
            ->where('status', ReservationStatus::Approved)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->count();
    }

    #[Computed]
    public function approvedReservations(): Collection
    {
        $orgId  = $this->getEffectiveOrganizationId();
        $search = mb_trim($this->reservationSearch);

        return Reservation::query()
            ->with(['user.organizations', 'item', 'itemUnit'])
            ->where('status', ReservationStatus::Approved)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $cleanId = mb_ltrim($search, '#');
                $term    = '%'.$search.'%';

                $q->where(function (Builder $sub) use ($term, $cleanId): void {
                    if (ctype_digit($cleanId)) {
                        $sub->orWhere('id', (int) $cleanId);
                    }

                    $sub->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('item', fn (Builder $i) => $i->where('name', 'like', $term))
                        ->orWhereHas('itemUnit', fn (Builder $unit) => $unit->where('asset_tag', 'like', $term));
                });
            })
            ->orderBy('start_time')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function pendingApprovedReservations(): Collection
    {
        return $this->approvedReservations;
    }

    #[Computed]
    public function items(): Collection
    {
        $search = mb_trim($this->itemSearch);

        if (mb_strlen($search) < 2) {
            return new Collection();
        }

        $orgId = $this->getEffectiveOrganizationId();
        $term  = '%'.$search.'%';

        return Item::query()
            ->with('organization')
            ->withCount([
                'units as available_units_count' => fn (Builder $q) => $q->whereIn('status', [ItemUnitStatus::Available, ItemUnitStatus::Reserved]),
            ])
            ->where('is_active', true)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->where(function (Builder $sub) use ($term): void {
                $sub->where('name', 'like', $term)
                    ->orWhere('brand', 'like', $term)
                    ->orWhere('model_number', 'like', $term);
            })
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedItem(): ?Item
    {
        if (! $this->selectedItemId) {
            return null;
        }

        return Item::with(['organization', 'category'])
            ->withCount([
                'units as available_units_count' => fn (Builder $q) => $q->whereIn('status', [ItemUnitStatus::Available, ItemUnitStatus::Reserved]),
            ])
            ->find($this->selectedItemId);
    }

    #[Computed]
    public function matchingUnits(): Collection
    {
        $search = mb_trim($this->unitSearch);

        if (mb_strlen($search) < 2) {
            return new Collection();
        }

        $orgId = $this->getEffectiveOrganizationId();
        $term  = '%'.$search.'%';

        return ItemUnit::query()
            ->with(['item', 'organization'])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->selectedItemId, fn (Builder $q) => $q->where('item_id', $this->selectedItemId))
            ->whereIn('status', [ItemUnitStatus::Available, ItemUnitStatus::Reserved])
            ->where(function (Builder $sub) use ($term): void {
                $sub->where('asset_tag', 'like', $term)
                    ->orWhere('serial_number', 'like', $term);
            })
            ->orderBy('asset_tag')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function selectedUnit(): ?ItemUnit
    {
        if (! $this->selectedUnitId) {
            return null;
        }

        return ItemUnit::with(['item', 'organization'])->find($this->selectedUnitId);
    }

    #[Computed]
    public function availableUnits(): Collection
    {
        if (! $this->selectedItemId) {
            return new Collection();
        }

        $orgId = $this->getEffectiveOrganizationId();

        return ItemUnit::query()
            ->where('item_id', $this->selectedItemId)
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->whereIn('status', [ItemUnitStatus::Available, ItemUnitStatus::Reserved])
            ->orderBy('asset_tag')
            ->get();
    }

    #[Computed]
    public function activeLoansForCheckin(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Loan::query()
            ->with(['user', 'item', 'itemUnit'])
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when(! empty($this->checkinSearch), function (Builder $q): void {
                $term = '%'.mb_trim($this->checkinSearch).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->whereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $term))
                        ->orWhereHas('itemUnit', fn ($unit) => $unit->where('asset_tag', 'like', $term)->orWhere('serial_number', 'like', $term));
                });
            })
            ->orderBy('due_at')
            ->limit(15)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.circulation.desk');
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

    protected function findAccessibleLoan(int $id): Loan
    {
        $loan = Loan::with(['itemUnit', 'user', 'item'])->findOrFail($id);
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            $userOrg = $user->organization;

            if ($userOrg && $loan->organization_id !== $userOrg->id) {
                abort(403, 'You are not authorized to access loans from another college.');
            }
        }

        return $loan;
    }
}
