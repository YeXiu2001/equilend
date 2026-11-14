<?php

declare(strict_types=1);

namespace App\Livewire\Borrower;

use App\Models\Loan;
use App\Models\Payment;
use App\Models\Penalty;
use Livewire\Component;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use Livewire\WithFileUploads;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\OrganizationPaymentMethod;
use Illuminate\Database\Eloquent\Collection;

class Penalties extends Component
{
    use Alert;
    use WithFileUploads;

    public bool $payModal = false;

    public string $payScope = 'all';

    public ?int $selectedPenaltyId = null;

    public ?int $selectedChannelId = null;

    public string $paymentAmount = '';

    public string $referenceNumber = '';

    public mixed $receiptImage = null;

    public string $paymentNotes = '';

    public bool $proofModal = false;

    public ?string $viewingProofUrl = null;

    public bool $loanModal = false;

    public ?int $viewingLoanId = null;

    /**
     * @return Collection<int, Penalty>
     */
    #[Computed]
    public function penalties(): Collection
    {
        return Penalty::query()
            ->with(['organization', 'itemUnit.item', 'loan.item', 'loan.itemUnit'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    #[Computed]
    public function payments(): Collection
    {
        return Payment::query()
            ->with(['organization', 'paymentMethod', 'penalty'])
            ->where('user_id', Auth::id())
            ->latest('paid_at')
            ->get();
    }

    /**
     * @return Collection<int, OrganizationPaymentMethod>
     */
    #[Computed]
    public function paymentChannels(): Collection
    {
        $user  = Auth::user();
        $orgId = $user?->organization?->id;

        if (! $orgId) {
            $firstPenalty = $this->penalties->first();
            $orgId        = $firstPenalty?->organization_id;
        }

        if (! $orgId) {
            return new Collection();
        }

        return OrganizationPaymentMethod::query()
            ->with('paymentMethod')
            ->where('organization_id', $orgId)
            ->enabled()
            ->whereHas('paymentMethod', function ($query): void {
                $query->where('is_active', true)
                    ->where('payment_code', '!=', 'cash');
            })
            ->get();
    }

    /**
     * Penalty IDs that currently have a payment awaiting admin verification.
     *
     * @return list<int>
     */
    #[Computed]
    public function pendingPenaltyIds(): array
    {
        return $this->payments
            ->where('status', PaymentStatus::PendingVerification)
            ->pluck('penalty_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Finalized, unsettled penalties (with returned items) that do not yet have a pending verification payment.
     *
     * @return Collection<int, Penalty>
     */
    #[Computed]
    public function unsettledPenalties(): Collection
    {
        $pendingIds = $this->pendingPenaltyIds;

        return $this->penalties->filter(function (Penalty $penalty) use ($pendingIds): bool {
            return $penalty->isPayableByBorrower()
                && ! in_array($penalty->id, $pendingIds, true);
        })->values();
    }

    #[Computed]
    public function selectedChannel(): ?OrganizationPaymentMethod
    {
        if (! $this->selectedChannelId) {
            return null;
        }

        return $this->paymentChannels->firstWhere('id', $this->selectedChannelId);
    }

    #[Computed]
    public function selectedPenalty(): ?Penalty
    {
        if (! $this->selectedPenaltyId) {
            return null;
        }

        return $this->penalties->firstWhere('id', $this->selectedPenaltyId);
    }

    public function openPayModal(?int $penaltyId = null): void
    {
        if ($this->paymentChannels->isEmpty()) {
            $this->error('No online payment channels are currently configured for your college.');

            return;
        }

        if ($this->paymentChannels->isEmpty()) {
            $this->error('This organization does not allow online payment. Please settle your penalty in cash over the counter.');

            return;
        }

        $this->resetValidation();
        $this->reset(['referenceNumber', 'receiptImage', 'paymentNotes']);
        $this->selectedChannelId = $this->paymentChannels->first()?->id;

        if ($penaltyId !== null) {
            $penalty = Penalty::with('loan')->where('user_id', Auth::id())->findOrFail($penaltyId);

            if ($penalty->status === PenaltyStatus::PendingApproval) {
                $this->error('This automated penalty is awaiting admin approval before it can be paid.');

                return;
            }

            if ($penalty->loan_id !== null && $penalty->loan?->returned_at === null) {
                $this->error('You can only pay this penalty after the borrowed item has been returned.');

                return;
            }

            if (! $penalty->isPayableByBorrower()) {
                $this->error('This penalty has already been settled.');

                return;
            }

            if (in_array($penalty->id, $this->pendingPenaltyIds, true)) {
                $this->info('A payment receipt for this penalty is already awaiting verification.');

                return;
            }

            $this->payScope          = 'single';
            $this->selectedPenaltyId = $penalty->id;
            $this->paymentAmount     = number_format((float) $penalty->balance_remaining, 2, '.', '');
        } else {
            if ($this->unsettledPenalties->isEmpty()) {
                $this->info('You have no remaining unsettled penalties awaiting payment submission.');

                return;
            }

            $this->payScope          = 'all';
            $this->selectedPenaltyId = null;
            $this->paymentAmount     = number_format((float) $this->unsettledPenalties->sum('balance_remaining'), 2, '.', '');
        }

        $this->payModal = true;
    }

    public function submitOnlinePayment(): void
    {
        $channel = $this->selectedChannelId
            ? $this->paymentChannels->firstWhere('id', $this->selectedChannelId)
            : null;

        if (! $channel) {
            $this->addError('selectedChannelId', 'Please select a valid payment channel.');

            return;
        }

        $rules = [
            'selectedChannelId' => ['required', 'integer'],
            'receiptImage'      => ['required', 'image', 'max:5120'],
            'referenceNumber'   => ['nullable', 'string', 'max:100'],
            'paymentNotes'      => ['nullable', 'string', 'max:500'],
        ];

        $targetPenalty = null;

        if ($this->payScope === 'single') {
            $targetPenalty = Penalty::with('loan')->where('user_id', Auth::id())->findOrFail($this->selectedPenaltyId);

            if (! $targetPenalty->isPayableByBorrower()) {
                $this->error('This penalty is not eligible for payment until the item is returned and the penalty is approved.');

                return;
            }

            if (in_array($targetPenalty->id, $this->pendingPenaltyIds, true)) {
                $this->error('A payment receipt for this penalty is already pending verification.');

                return;
            }

            $this->paymentAmount = number_format((float) $targetPenalty->balance_remaining, 2, '.', '');
        }

        $this->validate($rules, [
            'selectedChannelId.required' => 'Please select a payment channel.',
            'receiptImage.required'      => 'Please upload a screenshot or photo of your payment receipt.',
            'receiptImage.image'         => 'The uploaded receipt must be a valid image file.',
        ]);

        $targets = $this->payScope === 'single'
            ? new Collection([$targetPenalty])
            : $this->unsettledPenalties;

        if ($targets->isEmpty()) {
            $this->error('No unsettled penalties found to pay.');

            return;
        }

        $proofPath = $this->receiptImage->store('payment-proofs', 'public');
        $ref       = mb_trim($this->referenceNumber) !== '' ? mb_trim($this->referenceNumber) : null;
        $notes     = mb_trim($this->paymentNotes) !== '' ? mb_trim($this->paymentNotes) : null;

        DB::transaction(function () use ($targets, $channel, $proofPath, $ref, $notes): void {
            foreach ($targets as $penalty) {
                $amount = round((float) $penalty->balance_remaining, 2);

                Payment::create([
                    'organization_id'                => $penalty->organization_id,
                    'penalty_id'                     => $penalty->id,
                    'user_id'                        => Auth::id(),
                    'payment_method_id'              => $channel->payment_method_id,
                    'organization_payment_method_id' => $channel->id,
                    'amount_paid'                    => $amount,
                    'reference_number'               => $ref,
                    'payment_proof_url'              => $proofPath,
                    'paid_at'                        => now(),
                    'status'                         => PaymentStatus::PendingVerification,
                    'notes'                          => $notes,
                ]);
            }
        });

        $this->payModal     = false;
        $this->receiptImage = null;
        unset($this->payments, $this->pendingPenaltyIds, $this->unsettledPenalties);

        $this->success('Payment receipt submitted! An administrator will verify your payment shortly.');
    }

    public function openProofModal(string $url): void
    {
        $this->viewingProofUrl = $url;
        $this->proofModal      = true;
    }

    public function openLoanModal(int $loanId): void
    {
        $loan = Loan::where('user_id', Auth::id())->findOrFail($loanId);

        $this->viewingLoanId = $loan->id;
        $this->loanModal     = true;
    }

    #[Computed]
    public function viewingLoan(): ?Loan
    {
        if (! $this->viewingLoanId) {
            return null;
        }

        return Loan::query()
            ->with(['organization', 'item', 'itemUnit', 'issuedBy', 'receivedBy'])
            ->where('user_id', Auth::id())
            ->find($this->viewingLoanId);
    }

    public function render(): View
    {
        return view('livewire.borrower.penalties');
    }
}
