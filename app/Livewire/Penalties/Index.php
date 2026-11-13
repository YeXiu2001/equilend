<?php

declare(strict_types=1);

namespace App\Livewire\Penalties;

use App\Models\User;
use DomainException;
use App\Models\Penalty;
use Livewire\Component;
use App\Enums\PenaltyType;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use Livewire\WithPagination;
use App\Models\PaymentMethod;
use InvalidArgumentException;
use App\Livewire\Traits\Alert;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\OrganizationPaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Notifications\PenaltyImposedNotification;

class Index extends Component
{
    use Alert;
    use WithPagination;

    public string $search = '';

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    public string $selectedOrganizationId = 'all';

    // Assess Penalty Modal
    public bool $assessModal = false;

    public ?int $assessUserId = null;

    public string $assessType = 'damage';

    public string $assessAmount = '';

    public string $assessNotes = '';

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

    public function openAssessModal(): void
    {
        $this->reset(['assessUserId', 'assessAmount', 'assessNotes']);
        $this->assessType  = 'damage';
        $this->assessModal = true;
    }

    public function submitAssessPenalty(): void
    {
        $this->validate([
            'assessUserId' => ['required', 'exists:users,id'],
            'assessType'   => ['required', 'in:overdue,damage,loss,other'],
            'assessAmount' => ['required', 'numeric', 'min:1'],
            'assessNotes'  => ['required', 'string', 'min:3'],
        ], [
            'assessUserId.required' => 'Please select a borrower.',
            'assessAmount.required' => 'Please enter a penalty amount.',
            'assessNotes.required'  => 'Please provide assessment remarks.',
        ]);

        $staff    = Auth::user();
        $borrower = User::findOrFail($this->assessUserId);
        $orgId    = $this->getEffectiveOrganizationId()
            ?? $borrower->organization?->id
            ?? $staff->organization?->id;

        if (! $orgId) {
            $this->error('Unable to determine the college organization for this penalty.');

            return;
        }

        if (! $staff->hasRole('superadmin') && $staff->organization && $orgId !== $staff->organization->id) {
            abort(403, 'You cannot assess penalties for another college.');
        }

        $amount  = round((float) $this->assessAmount, 2);
        $penalty = Penalty::create([
            'organization_id'   => $orgId,
            'user_id'           => $borrower->id,
            'type'              => PenaltyType::from($this->assessType),
            'amount'            => $amount,
            'paid_amount'       => 0.00,
            'balance_remaining' => $amount,
            'status'            => PenaltyStatus::Unpaid,
            'assessment_notes'  => mb_trim($this->assessNotes),
            'assessed_by'       => $staff->id,
        ]);

        $borrower->notify(new PenaltyImposedNotification($penalty));

        $this->assessModal = false;
        $this->success("Penalty #{$penalty->id} (₱".number_format($amount, 2).") assessed against {$borrower->name}.");
    }

    public function openPaymentModal(int $penaltyId): void
    {
        $penalty = $this->findAccessiblePenalty($penaltyId);

        if (in_array($penalty->status, [PenaltyStatus::Paid, PenaltyStatus::Waived, PenaltyStatus::Voided], true)) {
            $this->error('This penalty has already been settled or closed.');

            return;
        }

        $cash = PaymentMethod::where('payment_code', 'cash')->first() ?? PaymentMethod::active()->first();

        $this->payingPenaltyId = $penalty->id;
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
        $isCash  = $method?->payment_code === 'cash';

        $this->validate([
            'paymentMethodId' => ['required', 'exists:payment_methods,id'],
            'paymentAmount'   => ['required', 'numeric', 'min:0.01', 'max:'.$penalty->balance_remaining],
            'referenceNumber' => $isCash ? ['nullable', 'string', 'max:100'] : ['required', 'string', 'min:4', 'max:100'],
        ], [
            'paymentMethodId.required' => 'Please select a payment method.',
            'referenceNumber.required' => 'A reference number is required for digital wallet payments.',
        ]);

        $orgPaymentMethod = null;

        if ($method && ! $isCash) {
            $orgPaymentMethod = OrganizationPaymentMethod::where('organization_id', $penalty->organization_id)
                ->where('payment_method_id', $method->id)
                ->first();
        }

        try {
            $payment = $penalty->recordPayment((float) $this->paymentAmount, [
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
    public function borrowers(): Collection
    {
        $orgId = $this->getEffectiveOrganizationId();

        return User::query()
            ->when($orgId !== null, fn (Builder $q) => $q->whereHas('organizations', fn (Builder $oq) => $oq->where('organizations.id', $orgId)))
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function penalties(): LengthAwarePaginator
    {
        $orgId = $this->getEffectiveOrganizationId();

        return Penalty::query()
            ->with(['organization', 'user', 'itemUnit.item', 'assessedBy'])
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
