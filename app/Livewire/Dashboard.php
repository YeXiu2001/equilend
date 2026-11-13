<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Loan;
use App\Models\User;
use App\Models\Payment;
use App\Models\Penalty;
use Livewire\Component;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Models\Reservation;
use App\Enums\PaymentStatus;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ReservationStatus;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class Dashboard extends Component
{
    public string $selectedOrganizationId = 'all';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user && $user->hasRole('organization_admin') && ! $user->hasRole('superadmin')) {
            $this->selectedOrganizationId = $user->organization ? (string) $user->organization->id : 'all';
        }
    }

    /**
     * Determine whether the authenticated user should see the staff/admin command center.
     */
    #[Computed]
    public function isStaff(): bool
    {
        $user = Auth::user();

        return (bool) $user?->hasAnyRole(['superadmin', 'organization_admin']);
    }

    /**
     * Determine whether the authenticated user is a superadmin.
     */
    #[Computed]
    public function isSuperAdmin(): bool
    {
        return (bool) Auth::user()?->hasRole('superadmin');
    }

    /**
     * Active organizations for the superadmin college selector.
     *
     * @return Collection<int, Organization>
     */
    #[Computed]
    public function organizations(): Collection
    {
        return Organization::where('is_active', true)->orderBy('name')->get();
    }

    // -------------------------------------------------------------------------
    // Student (Borrower) Computed Properties
    // -------------------------------------------------------------------------

    /**
     * Borrowing eligibility status and block reasons for the current student.
     *
     * @return array{can_borrow: bool, reasons: list<string>}
     */
    #[Computed]
    public function studentEligibility(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return [
            'can_borrow' => $user->canBorrow(),
            'reasons'    => $user->getBorrowingBlockReasons(),
        ];
    }

    /**
     * Student KPI summary cards.
     *
     * @return array<string, int|float>
     */
    #[Computed]
    public function studentStats(): array
    {
        /** @var User $user */
        $user = Auth::user();

        $activeLoans = $user->loans()
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->whereNull('returned_at')
            ->get();

        $dueSoonOrOverdue = $activeLoans->filter(
            fn (Loan $loan): bool => $loan->isOverdue() || ($loan->due_at && $loan->due_at->lte(now()->addHours(24)))
        )->count();

        $openReservations = $user->reservations()
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved])
            ->count();

        return [
            'active_loans'      => $activeLoans->count(),
            'urgent_loans'      => $dueSoonOrOverdue,
            'open_reservations' => $openReservations,
            'outstanding_fines' => $user->getOutstandingFinesTotal(),
        ];
    }

    /**
     * Student's active/overdue loans ordered by nearest due date.
     *
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function studentActiveLoans(): Collection
    {
        return Auth::user()
            ->loans()
            ->with(['item', 'itemUnit', 'organization'])
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->whereNull('returned_at')
            ->orderBy('due_at')
            ->limit(5)
            ->get();
    }

    /**
     * Student's pending and approved upcoming reservations.
     *
     * @return Collection<int, Reservation>
     */
    #[Computed]
    public function studentReservations(): Collection
    {
        return Auth::user()
            ->reservations()
            ->with(['item', 'itemUnit', 'organization'])
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Approved])
            ->orderBy('start_time')
            ->limit(5)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Staff / Admin Computed Properties
    // -------------------------------------------------------------------------

    /**
     * Operational KPIs for college admins and superadmin.
     *
     * @return array<string, int|float>
     */
    #[Computed]
    public function staffStats(): array
    {
        $orgId = $this->getEffectiveOrganizationId();

        $totalUnits = ItemUnit::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->count();

        $availableUnits = ItemUnit::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', ItemUnitStatus::Available)
            ->count();

        $activeLoans = Loan::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->whereNull('returned_at')
            ->count();

        $overdueLoans = Loan::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->overdue()
            ->count();

        $pendingReservations = Reservation::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->pending()
            ->count();

        $unsettledFines = round((float) Penalty::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->unsettled()
            ->sum('balance_remaining'), 2);

        $todaysCollections = round((float) Payment::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', PaymentStatus::Verified)
            ->whereDate('paid_at', today())
            ->sum('amount_paid'), 2);

        return [
            'total_units'          => $totalUnits,
            'available_units'      => $availableUnits,
            'active_loans'         => $activeLoans,
            'overdue_loans'        => $overdueLoans,
            'pending_reservations' => $pendingReservations,
            'unsettled_fines'      => $unsettledFines,
            'todays_collections'   => $todaysCollections,
        ];
    }

    /**
     * Oldest pending booking requests awaiting staff review.
     *
     * @return Collection<int, Reservation>
     */
    #[Computed]
    public function pendingRequestsQueue(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Reservation::query()
            ->with(['user', 'item', 'organization'])
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->pending()
            ->orderBy('start_time')
            ->limit(5)
            ->get();
    }

    /**
     * Overdue and due-today loans requiring urgent follow-up.
     *
     * @return Collection<int, Loan>
     */
    #[Computed]
    public function urgentLoansQueue(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Loan::query()
            ->with(['user', 'item', 'itemUnit', 'organization'])
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))
            ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
            ->whereNull('returned_at')
            ->where('due_at', '<=', now()->endOfDay())
            ->orderBy('due_at')
            ->limit(5)
            ->get();
    }

    /**
     * Per-college snapshot comparison table when superadmin views All Colleges.
     *
     * @return array<int, array<string, mixed>>
     */
    #[Computed]
    public function collegeSnapshot(): array
    {
        if (! $this->isSuperAdmin || $this->getEffectiveOrganizationId() !== null) {
            return [];
        }

        $colleges = Organization::where('is_active', true)->orderBy('code')->get();
        $rows     = [];

        foreach ($colleges as $college) {
            $totalUnits     = ItemUnit::where('organization_id', $college->id)->count();
            $availableUnits = ItemUnit::where('organization_id', $college->id)
                ->where('status', ItemUnitStatus::Available)
                ->count();
            $activeLoans = Loan::where('organization_id', $college->id)
                ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
                ->whereNull('returned_at')
                ->count();
            $overdueLoans = Loan::where('organization_id', $college->id)
                ->overdue()
                ->count();
            $pendingRequests = Reservation::where('organization_id', $college->id)
                ->pending()
                ->count();
            $unsettledFines = round((float) Penalty::where('organization_id', $college->id)
                ->unsettled()
                ->sum('balance_remaining'), 2);

            $rows[] = [
                'id'               => $college->id,
                'code'             => $college->code,
                'name'             => $college->name,
                'total_units'      => $totalUnits,
                'available_units'  => $availableUnits,
                'active_loans'     => $activeLoans,
                'overdue_loans'    => $overdueLoans,
                'pending_requests' => $pendingRequests,
                'unsettled_fines'  => $unsettledFines,
            ];
        }

        return $rows;
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }

    /**
     * Resolve the effective organization scope for staff metrics.
     */
    protected function getEffectiveOrganizationId(): ?int
    {
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            return $user->organization?->id;
        }

        if ($this->selectedOrganizationId !== 'all' && is_numeric($this->selectedOrganizationId)) {
            return (int) $this->selectedOrganizationId;
        }

        return null;
    }
}
