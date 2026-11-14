<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Models\Loan;
use DomainException;
use App\Models\Payment;
use App\Models\Penalty;
use Livewire\Component;
use App\Enums\PaymentStatus;
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

    public string $methodFilter = 'all';

    public string $statusFilter = 'all';

    public string $selectedOrganizationId = 'all';

    // Record Payment Modal
    public bool $recordModal = false;

    public ?int $selectedPenaltyId = null;

    public ?int $paymentMethodId = null;

    public string $amountPaid = '';

    public string $referenceNumber = '';

    public string $notes = '';

    // View Receipt Modal
    public bool $receiptModal = false;

    public ?int $viewingPaymentId = null;

    public string $rejectReason = '';

    // View Loan Details Modal
    public bool $loanModal = false;

    public ?int $viewingLoanId = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to payments and receipts.');
        }

        if (! $user->hasRole('superadmin')) {
            $this->selectedOrganizationId = $user->organization ? (string) $user->organization->id : 'all';
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
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

    public function updatedSelectedPenaltyId($value): void
    {
        if (! $value) {
            $this->amountPaid = '';

            return;
        }

        $penalty = Penalty::find((int) $value);

        if ($penalty) {
            $this->amountPaid = number_format((float) $penalty->balance_remaining, 2, '.', '');
        }
    }

    public function openRecordModal(): void
    {
        $cash = PaymentMethod::where('payment_code', 'cash')->first() ?? PaymentMethod::active()->first();

        $this->reset(['selectedPenaltyId', 'amountPaid', 'referenceNumber', 'notes']);
        $this->paymentMethodId = $cash?->id;
        $this->recordModal     = true;
    }

    public function submitPayment(): void
    {
        $this->validate([
            'selectedPenaltyId' => ['required', 'exists:penalties,id'],
            'paymentMethodId'   => ['required', 'exists:payment_methods,id'],
            'amountPaid'        => ['required', 'numeric', 'min:0.01'],
        ], [
            'selectedPenaltyId.required' => 'Please select an unsettled penalty.',
            'paymentMethodId.required'   => 'Please select a payment channel.',
        ]);

        $penalty = Penalty::findOrFail($this->selectedPenaltyId);
        $user    = Auth::user();

        if (! $user->hasRole('superadmin') && $user->organization && $penalty->organization_id !== $user->organization->id) {
            abort(403, 'You cannot record payments for another college.');
        }

        $method = PaymentMethod::findOrFail($this->paymentMethodId);
        $isCash = $method->payment_code === 'cash';

        if (! $isCash) {
            $this->validate([
                'referenceNumber' => ['required', 'string', 'min:4', 'max:100'],
            ], [
                'referenceNumber.required' => 'A reference number is required for digital wallet transfers.',
            ]);
        }

        $orgPaymentMethod = $isCash
            ? null
            : OrganizationPaymentMethod::where('organization_id', $penalty->organization_id)
                ->where('payment_method_id', $method->id)
                ->first();

        $fullAmount = round((float) $penalty->balance_remaining, 2);

        if (abs((float) $this->amountPaid - $fullAmount) >= 0.01) {
            $this->addError('amountPaid', 'Partial payments are not allowed. Full penalty balance (₱'.number_format($fullAmount, 2).') is required.');

            return;
        }

        try {
            $payment = $penalty->recordPayment($fullAmount, [
                'payment_method_id'              => $method->id,
                'organization_payment_method_id' => $orgPaymentMethod?->id,
                'reference_number'               => ! empty($this->referenceNumber) ? mb_trim($this->referenceNumber) : null,
                'received_by'                    => $user->id,
                'status'                         => PaymentStatus::Verified,
                'notes'                          => ! empty($this->notes) ? mb_trim($this->notes) : null,
            ]);

            $this->recordModal = false;
            $this->success("Payment recorded! Official Receipt {$payment->receipt_number} issued.");
        } catch (InvalidArgumentException|DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    public function viewReceipt(int $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);
        $user    = Auth::user();

        if ($user && ! $user->hasRole('superadmin') && $user->organization && $payment->organization_id !== $user->organization->id) {
            abort(403, 'You are not authorized to view receipts from another college.');
        }

        $this->viewingPaymentId = $payment->id;
        $this->rejectReason     = '';
        $this->receiptModal     = true;
    }

    public function confirmPayment(int $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);
        $user    = Auth::user();

        if ($user && ! $user->hasRole('superadmin') && $user->organization && $payment->organization_id !== $user->organization->id) {
            abort(403, 'You are not authorized to verify payments from another college.');
        }

        try {
            $verifiedCount = 0;

            DB::transaction(function () use ($payment, $user, &$verifiedCount): void {
                foreach ($payment->siblingPendingPayments() as $pending) {
                    $pending->verify((int) $user->id);
                    $verifiedCount++;
                }
            });

            $this->receiptModal = false;
            $this->success("Payment confirmed! {$verifiedCount} ".($verifiedCount === 1 ? 'penalty has' : 'penalties have').' been marked as paid.');
        } catch (DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    public function rejectPayment(int $paymentId): void
    {
        $payment = Payment::findOrFail($paymentId);
        $user    = Auth::user();

        if ($user && ! $user->hasRole('superadmin') && $user->organization && $payment->organization_id !== $user->organization->id) {
            abort(403, 'You are not authorized to reject payments from another college.');
        }

        try {
            DB::transaction(function () use ($payment, $user): void {
                foreach ($payment->siblingPendingPayments() as $pending) {
                    $pending->reject((int) $user->id, $this->rejectReason);
                }
            });

            $this->receiptModal = false;
            $this->rejectReason = '';
            $this->warning('Payment receipt rejected. The penalty remains unpaid.');
        } catch (DomainException $e) {
            $this->error($e->getMessage());
        }
    }

    #[Computed]
    public function viewingPayment(): ?Payment
    {
        if (! $this->viewingPaymentId) {
            return null;
        }

        return Payment::with(['organization', 'penalty.itemUnit.item', 'user', 'receivedBy', 'paymentMethod'])
            ->find($this->viewingPaymentId);
    }

    public function openLoanModal(int $loanId): void
    {
        $loan = Loan::findOrFail($loanId);
        $user = Auth::user();

        if ($user && ! $user->hasRole('superadmin') && $user->organization && $loan->organization_id !== $user->organization->id) {
            abort(403, 'You are not authorized to view loans from another college.');
        }

        $this->viewingLoanId = $loan->id;
        $this->loanModal     = true;
    }

    #[Computed]
    public function viewingLoan(): ?Loan
    {
        if (! $this->viewingLoanId) {
            return null;
        }

        return Loan::with(['organization', 'user', 'item', 'itemUnit', 'issuedBy', 'receivedBy'])
            ->find($this->viewingLoanId);
    }

    #[Computed]
    public function organizations(): Collection
    {
        return Organization::active()->orderBy('code')->get();
    }

    #[Computed]
    public function paymentMethods(): Collection
    {
        return PaymentMethod::active()->orderBy('name')->get();
    }

    #[Computed]
    public function unsettledPenalties(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Penalty::query()
            ->with(['user', 'organization'])
            ->unsettled()
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function payments(): LengthAwarePaginator
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Payment::query()
            ->with(['organization', 'penalty.loan.item', 'user', 'receivedBy', 'paymentMethod'])
            ->when($orgId !== null, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->when($this->methodFilter !== 'all', fn (Builder $q) => $q->where('payment_method_id', (int) $this->methodFilter))
            ->when($this->statusFilter !== 'all', fn (Builder $q) => $q->where('status', $this->statusFilter))
            ->when(! empty($this->search), function (Builder $q): void {
                $raw     = mb_trim($this->search);
                $cleanId = mb_ltrim($raw, '#');
                $term    = '%'.$raw.'%';

                $q->where(function (Builder $sub) use ($term, $cleanId): void {
                    $sub->where('receipt_number', 'like', $term)
                        ->orWhere('reference_number', 'like', $term)
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));

                    if (ctype_digit($cleanId)) {
                        $sub->orWhereHas('penalty', fn (Builder $p) => $p->where('loan_id', (int) $cleanId));
                    }
                });
            })
            ->latest('paid_at')
            ->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.payments.index');
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
}
