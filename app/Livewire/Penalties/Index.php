<?php

declare(strict_types=1);

namespace App\Livewire\Penalties;

use DomainException;
use App\Models\Payment;
use App\Models\Penalty;
use Livewire\Component;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use Livewire\WithPagination;
use App\Models\PaymentMethod;
use InvalidArgumentException;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\OrganizationPaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class Index extends Component
{
    use Alert;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    public string $selectedOrganizationId = 'all';

    // Record Payment Modal
    public bool $paymentModal = false;

    public ?int $payingPenaltyId = null;

    public ?int $paymentMethodId = null;

    public string $paymentAmount = '';

    public string $referenceNumber = '';

    public string $paymentNotes = '';

    // Waive / Void Modal
    public bool $waiveModal = false;

    public ?int $waivingPenaltyId = null;

    public string $waiveAction = 'waive';

    public string $waiveReason = '';

    // Verify Online Payment Modal
    public bool $verifyModal = false;

    public ?int $verifyingPaymentId = null;

    public string $rejectReason = '';

    // Approve Automated Penalty Modal
    public bool $approveModal = false;

    public ?int $approvingPenaltyId = null;

    public string $approveAmount = '';

    public string $approveNotes = '';

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to finance and penalties management.');
        }

        if (! $user->hasRole('superadmin')) {
            $this->selectedOrganizationId = $user->organization ? (string) $user->organization->id : 'all';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedOrganizationId(): void
    {
        $this->resetPage();
    }

    public function openPaymentModal(int $penaltyId): void
    {
        $penalty = $this->findAccessiblePenalty($penaltyId);

        if (in_array($penalty->status, [PenaltyStatus::PendingApproval, PenaltyStatus::Paid, PenaltyStatus::Waived, PenaltyStatus::Voided], true)) {
            $this->error($penalty->status === PenaltyStatus::PendingApproval
                ? 'Please approve this automated penalty before recording a payment.'
                : 'This penalty has already been settled or closed.');

            return;
        }

        $cash = PaymentMethod::where('payment_code', 'cash')->first() ?? PaymentMethod::active()->first();

        $this->payingPenaltyId = $penalty->id;
        unset($this->paymentMethods);
        $this->resetValidation();
        $this->paymentMethodId = $cash?->id;
        $this->paymentAmount   = number_format((float) $penalty->balance_remaining, 2, '.', '');
        $this->referenceNumber = '';
        $this->paymentNotes    = '';
        $this->paymentModal    = true;
    }

    public function submitPayment(): void
    {
        if (! $this->payingPenaltyId) {
            return;
        }

        $penalty = $this->findAccessiblePenalty($this->payingPenaltyId);
        $method  = $this->paymentMethodId ? PaymentMethod::find($this->paymentMethodId) : null;
        $isCash  = $method?->isCash() ?? false;

        $this->validate([
            'paymentMethodId' => ['required', 'exists:payment_methods,id'],
            'referenceNumber' => $isCash ? ['nullable', 'string', 'max:100'] : ['required', 'string', 'min:4', 'max:100'],
        ], [
            'paymentMethodId.required' => 'Please select a payment method.',
            'referenceNumber.required' => 'A reference number is required for digital wallet payments.',
        ]);

        $fullAmount = round((float) $penalty->balance_remaining, 2);

        if (abs((float) $this->paymentAmount - $fullAmount) >= 0.01) {
            $this->addError('paymentAmount', 'Partial payments are not allowed. Full penalty balance (₱'.number_format($fullAmount, 2).') is required.');

            return;
        }

        $orgPaymentMethod = null;

        if ($method && ! $isCash) {
            $orgPaymentMethod = OrganizationPaymentMethod::where('organization_id', $penalty->organization_id)
                ->where('payment_method_id', $method->id)
                ->where('is_enabled', true)
                ->first();

            if (! $orgPaymentMethod) {
                $this->addError('paymentMethodId', 'The selected payment channel is not enabled for this organization.');

                return;
            }
        }

        try {
            $payment = $penalty->recordPayment($fullAmount, [
                'payment_method_id'              => $method?->id,
                'organization_payment_method_id' => $orgPaymentMethod?->id,
                'reference_number'               => ! empty($this->referenceNumber) ? mb_trim($this->referenceNumber) : null,
                'received_by'                    => Auth::id(),
                'status'                         => PaymentStatus::Verified,
                'notes'                          => ! empty($this->paymentNotes) ? mb_trim($this->paymentNotes) : null,
            ]);

            $this->paymentModal    = false;
            $this->payingPenaltyId = null;

            $this->success("Payment recorded! Official Receipt {$payment->receipt_number} issued for ₱".number_format((float) $payment->amount_paid, 2).'.');
        } catch (InvalidArgumentException|DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    public function openWaiveModal(int $penaltyId, string $action = 'waive'): void
    {
        $penalty = $this->findAccessiblePenalty($penaltyId);

        if (in_array($penalty->status, [PenaltyStatus::Paid, PenaltyStatus::Voided], true)) {
            $this->error("Cannot {$action} a penalty that is already {$penalty->status->label()}.");

            return;
        }

        $this->waivingPenaltyId = $penalty->id;
        $this->waiveAction      = in_array($action, ['waive', 'void'], true) ? $action : 'waive';
        $this->waiveReason      = '';
        $this->waiveModal       = true;
    }

    public function submitWaiveOrVoid(): void
    {
        if (! $this->waivingPenaltyId) {
            return;
        }

        $this->validate([
            'waiveReason' => ['required', 'string', 'min:5'],
        ], [
            'waiveReason.required' => 'Administrative justification remarks are required.',
            'waiveReason.min'      => 'Please provide at least 5 characters of justification.',
        ]);

        $penalty = $this->findAccessiblePenalty($this->waivingPenaltyId);

        try {
            if ($this->waiveAction === 'void') {
                $penalty->void($this->waiveReason, (int) Auth::id());
                $this->success("Penalty #{$penalty->id} has been voided.");
            } else {
                $penalty->waive($this->waiveReason, (int) Auth::id());
                $this->success("Penalty #{$penalty->id} has been waived.");
            }

            $this->waiveModal       = false;
            $this->waivingPenaltyId = null;
            $this->waiveReason      = '';
        } catch (InvalidArgumentException|DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    public function openApproveModal(int $penaltyId): void
    {
        $penalty = $this->findAccessiblePenalty($penaltyId);

        if ($penalty->status !== PenaltyStatus::PendingApproval) {
            $this->error('Only pending approval penalties can be approved.');

            return;
        }

        $this->approvingPenaltyId = $penalty->id;
        $this->approveAmount      = number_format((float) $penalty->amount, 2, '.', '');
        $this->approveNotes       = (string) ($penalty->assessment_notes ?? '');
        $this->approveModal       = true;
    }

    public function submitApprovePenalty(): void
    {
        if (! $this->approvingPenaltyId) {
            return;
        }

        $this->validate([
            'approveAmount' => ['required', 'numeric', 'min:0.01'],
            'approveNotes'  => ['nullable', 'string', 'max:500'],
        ], [
            'approveAmount.required' => 'Please enter the finalized penalty amount.',
        ]);

        $penalty = $this->findAccessiblePenalty($this->approvingPenaltyId);

        try {
            $penalty->approve(
                (int) Auth::id(),
                (float) $this->approveAmount,
                $this->approveNotes
            );

            $this->approveModal       = false;
            $this->approvingPenaltyId = null;

            $this->success("Penalty #{$penalty->id} approved and finalized at ₱".number_format((float) $penalty->amount, 2).'.');
        } catch (InvalidArgumentException|DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    #[Computed]
    public function approvingPenalty(): ?Penalty
    {
        if (! $this->approvingPenaltyId) {
            return null;
        }

        return Penalty::with(['user', 'loan.item', 'organization'])
            ->find($this->approvingPenaltyId);
    }

    public function openVerifyModal(int $penaltyId): void
    {
        $penalty = $this->findAccessiblePenalty($penaltyId);

        $pendingPayment = $penalty->payments()
            ->where('status', PaymentStatus::PendingVerification)
            ->latest('id')
            ->first();

        if (! $pendingPayment) {
            $this->error('No pending payment receipt found for this penalty.');

            return;
        }

        $this->verifyingPaymentId = $pendingPayment->id;
        $this->rejectReason       = '';
        $this->verifyModal        = true;
    }

    public function confirmPendingPayment(bool $clearAllBorrowerPenalties = false): void
    {
        if (! $this->verifyingPaymentId) {
            return;
        }

        $payment = Payment::with(['penalty', 'user'])->findOrFail($this->verifyingPaymentId);
        $this->findAccessiblePenalty($payment->penalty_id);

        try {
            $verifiedCount = 0;

            DB::transaction(function () use ($payment, $clearAllBorrowerPenalties, &$verifiedCount): void {
                $siblings   = $payment->siblingPendingPayments();
                $coveredIds = [];
                $staffId    = (int) Auth::id();

                foreach ($siblings as $pending) {
                    $pending->verify($staffId);
                    $coveredIds[] = $pending->penalty_id;
                    $verifiedCount++;
                }

                if ($clearAllBorrowerPenalties) {
                    $remaining = Penalty::query()
                        ->where('user_id', $payment->user_id)
                        ->where('organization_id', $payment->organization_id)
                        ->whereNotIn('id', $coveredIds)
                        ->unsettled()
                        ->get();

                    foreach ($remaining as $extraPenalty) {
                        $extraPenalty->recordPayment((float) $extraPenalty->balance_remaining, [
                            'payment_method_id'              => $payment->payment_method_id,
                            'organization_payment_method_id' => $payment->organization_payment_method_id,
                            'reference_number'               => $payment->reference_number,
                            'payment_proof_url'              => $payment->payment_proof_url,
                            'received_by'                    => $staffId,
                            'status'                         => PaymentStatus::Verified,
                            'notes'                          => "Settled with verified receipt {$payment->receipt_number}",
                        ]);
                        $verifiedCount++;
                    }
                }
            });

            $this->verifyModal        = false;
            $this->verifyingPaymentId = null;

            $this->success("Payment confirmed! {$verifiedCount} ".($verifiedCount === 1 ? 'penalty has' : 'penalties have').' been marked as paid.');
        } catch (InvalidArgumentException|DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    public function rejectPendingPayment(): void
    {
        if (! $this->verifyingPaymentId) {
            return;
        }

        $payment = Payment::findOrFail($this->verifyingPaymentId);
        $this->findAccessiblePenalty($payment->penalty_id);

        try {
            DB::transaction(function () use ($payment): void {
                $staffId = (int) Auth::id();

                foreach ($payment->siblingPendingPayments() as $pending) {
                    $pending->reject($staffId, $this->rejectReason);
                }
            });

            $this->verifyModal        = false;
            $this->verifyingPaymentId = null;
            $this->rejectReason       = '';

            $this->warning('Payment receipt rejected. The penalty remains unpaid.');
        } catch (DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    #[Computed]
    public function verifyingPayment(): ?Payment
    {
        if (! $this->verifyingPaymentId) {
            return null;
        }

        return Payment::with(['penalty', 'user', 'paymentMethod', 'organization'])
            ->find($this->verifyingPaymentId);
    }

    /**
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function verifyingSiblings(): Collection
    {
        $payment = $this->verifyingPayment;

        if (! $payment) {
            return new Collection();
        }

        return $payment->siblingPendingPayments();
    }

    #[Computed]
    public function borrowerOtherUnsettledCount(): int
    {
        $payment = $this->verifyingPayment;

        if (! $payment) {
            return 0;
        }

        $coveredIds = $this->verifyingSiblings->pluck('penalty_id')->all();

        return Penalty::query()
            ->where('user_id', $payment->user_id)
            ->where('organization_id', $payment->organization_id)
            ->whereNotIn('id', $coveredIds)
            ->unsettled()
            ->count();
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::active()->orderBy('code')->get();
    }

    #[Computed]
    public function paymentMethods(): Collection
    {
        $orgId = $this->payingPenaltyId
            ? Penalty::whereKey($this->payingPenaltyId)->value('organization_id')
            : $this->getEffectiveOrganizationId();

        return PaymentMethod::active()
            ->where(function (Builder $query) use ($orgId): void {
                $query->where('payment_code', 'cash');

                if ($orgId !== null) {
                    $query->orWhereHas('organizationPaymentMethods', function (Builder $opm) use ($orgId): void {
                        $opm->where('organization_id', $orgId)
                            ->where('is_enabled', true);
                    });
                }
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function penalties(): LengthAwarePaginator
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Penalty::query()
            ->with(['organization', 'user', 'itemUnit.item', 'assessedBy', 'payments.paymentMethod', 'loan'])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->typeFilter !== 'all', fn (Builder $q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when(! empty($this->search), function (Builder $q): void {
                $term = '%'.mb_trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('assessment_notes', 'like', $term)
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                        ->orWhereHas('itemUnit', fn (Builder $unit) => $unit->where('asset_tag', 'like', $term));
                });
            })
            ->latest()
            ->paginate(12);
    }

    #[Computed]
    public function summaryStats(): array
    {
        $orgId = $this->getEffectiveOrganizationId();

        $base = Penalty::query()->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId));

        return [
            'outstanding_total' => (float) (clone $base)->unsettled()->sum('balance_remaining'),
            'unsettled_count'   => (clone $base)->unsettled()->count(),
            'collected_total'   => (float) (clone $base)->sum('paid_amount'),
            'waived_count'      => (clone $base)->where('status', PenaltyStatus::Waived)->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.penalties.index');
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

    protected function findAccessiblePenalty(int $id): Penalty
    {
        $penalty = Penalty::with(['organization', 'user', 'itemUnit'])->findOrFail($id);
        $user    = Auth::user();

        if ($user && ! $user->hasRole('superadmin')) {
            $userOrg = $user->organization;

            if ($userOrg && $penalty->organization_id !== $userOrg->id) {
                abort(403, 'You are not authorized to manage penalties from another college.');
            }
        }

        return $penalty;
    }
}
