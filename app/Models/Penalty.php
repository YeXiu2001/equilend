<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use App\Enums\PenaltyType;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Database\Factories\PenaltyFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penalty extends Model
{
    /** @use HasFactory<PenaltyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'loan_id',
        'user_id',
        'item_unit_id',
        'type',
        'amount',
        'paid_amount',
        'balance_remaining',
        'status',
        'assessment_notes',
        'assessed_by',
        'waived_reason',
    ];

    /**
     * Organization (college) this penalty belongs to.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Borrower (student or faculty member) assessed with this penalty.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for the borrower relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function borrower(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Staff or admin user who assessed this penalty.
     *
     * @return BelongsTo<User, $this>
     */
    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    /**
     * Associated loan if penalty originated from a circulation transaction.
     *
     * @return BelongsTo<Loan, $this>
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Associated physical unit if applicable.
     *
     * @return BelongsTo<ItemUnit, $this>
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class);
    }

    /**
     * Payment transactions applied toward this penalty.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Record a partial or full payment against this penalty.
     *
     * @param  array<string, mixed>  $paymentAttributes
     *
     * @throws InvalidArgumentException|DomainException
     */
    public function recordPayment(float $amount, array $paymentAttributes = []): Payment
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($amount, $paymentAttributes): Payment {
            $penalty = static::lockForUpdate()->findOrFail($this->id);

            if (in_array($penalty->status, [PenaltyStatus::PendingApproval, PenaltyStatus::Paid, PenaltyStatus::Waived, PenaltyStatus::Voided], true)) {
                throw new DomainException("Cannot record payment on a penalty with status {$penalty->status->label()}.");
            }

            $currentBalance = round((float) $penalty->balance_remaining, 2);

            if (abs($amount - $currentBalance) >= 0.01) {
                throw new DomainException("Partial payments and overpayments are not allowed. Payment amount ({$amount}) must equal the full remaining balance ({$currentBalance}).");
            }

            $newPaidAmount = round((float) $penalty->paid_amount + $currentBalance, 2);

            $penalty->paid_amount       = $newPaidAmount;
            $penalty->balance_remaining = 0.0;
            $penalty->status            = PenaltyStatus::Paid;
            $penalty->save();

            $payment = Payment::create(array_merge([
                'organization_id'                => $penalty->organization_id,
                'penalty_id'                     => $penalty->id,
                'user_id'                        => $penalty->user_id,
                'payment_method_id'              => $paymentAttributes['payment_method_id'] ?? null,
                'organization_payment_method_id' => $paymentAttributes['organization_payment_method_id'] ?? null,
                'amount_paid'                    => $amount,
                'reference_number'               => $paymentAttributes['reference_number'] ?? null,
                'payment_proof_url'              => $paymentAttributes['payment_proof_url'] ?? null,
                'paid_at'                        => $paymentAttributes['paid_at'] ?? now(),
                'status'                         => $paymentAttributes['status'] ?? PaymentStatus::Verified,
                'received_by'                    => $paymentAttributes['received_by'] ?? null,
                'notes'                          => $paymentAttributes['notes'] ?? null,
            ], isset($paymentAttributes['receipt_number']) ? ['receipt_number' => $paymentAttributes['receipt_number']] : []));

            $this->refresh();

            return $payment;
        });
    }

    /**
     * Approve and finalize a pending automated penalty so it becomes payable.
     *
     * @throws InvalidArgumentException|DomainException
     */
    public function approve(int $staffId, ?float $finalAmount = null, ?string $notes = null): void
    {
        if ($this->status !== PenaltyStatus::PendingApproval) {
            throw new DomainException("Only pending approval penalties can be approved (current status: {$this->status->label()}).");
        }

        $attributes = [
            'status'      => PenaltyStatus::Unpaid,
            'assessed_by' => $staffId,
        ];

        if ($finalAmount !== null) {
            $roundedAmount = round($finalAmount, 2);

            if ($roundedAmount <= 0) {
                throw new InvalidArgumentException('Approved penalty amount must be greater than zero.');
            }

            $attributes['amount']            = $roundedAmount;
            $attributes['balance_remaining'] = max(0.0, round($roundedAmount - (float) $this->paid_amount, 2));
        }

        if ($notes !== null && mb_trim($notes) !== '') {
            $attributes['assessment_notes'] = mb_trim($notes);
        }

        $this->update($attributes);
    }

    /**
     * Determine whether this penalty is finalized and eligible to be paid by the borrower.
     * Loan-linked penalties require the borrowed item to be returned first.
     */
    public function isPayableByBorrower(): bool
    {
        if (! in_array($this->status, [PenaltyStatus::Unpaid, PenaltyStatus::PartiallyPaid], true)) {
            return false;
        }

        if ((float) $this->balance_remaining <= 0) {
            return false;
        }

        if ($this->loan_id !== null) {
            $loan = $this->loan;

            return $loan !== null && $loan->returned_at !== null;
        }

        return true;
    }

    /**
     * Waive the remaining balance of this penalty with a mandatory justification reason.
     *
     * @throws InvalidArgumentException|DomainException
     */
    public function waive(string $reason, int $staffId): void
    {
        $trimmedReason = mb_trim($reason);

        if ($trimmedReason === '') {
            throw new InvalidArgumentException('A justification reason is required to waive a penalty.');
        }

        if (in_array($this->status, [PenaltyStatus::Paid, PenaltyStatus::Voided], true)) {
            throw new DomainException("Cannot waive a penalty with status {$this->status->label()}.");
        }

        $this->update([
            'status'            => PenaltyStatus::Waived,
            'balance_remaining' => 0,
            'waived_reason'     => $trimmedReason,
            'assessed_by'       => $this->assessed_by ?? $staffId,
        ]);
    }

    /**
     * Void this penalty with a mandatory justification reason.
     *
     * @throws InvalidArgumentException|DomainException
     */
    public function void(string $reason, int $staffId): void
    {
        $trimmedReason = mb_trim($reason);

        if ($trimmedReason === '') {
            throw new InvalidArgumentException('A justification reason is required to void a penalty.');
        }

        if ($this->status === PenaltyStatus::Paid) {
            throw new DomainException('Cannot void a penalty that has already been fully paid.');
        }

        $this->update([
            'status'            => PenaltyStatus::Voided,
            'balance_remaining' => 0,
            'waived_reason'     => $trimmedReason,
            'assessed_by'       => $this->assessed_by ?? $staffId,
        ]);
    }

    /**
     * Scope query to penalties that still have an unpaid balance.
     *
     * @param  Builder<Penalty>  $query
     */
    public function scopeUnsettled(Builder $query): void
    {
        $query->whereIn('status', [PenaltyStatus::Unpaid, PenaltyStatus::PartiallyPaid])
            ->where('balance_remaining', '>', 0);
    }

    /**
     * Scope query to a specific organization.
     *
     * @param  Builder<Penalty>  $query
     */
    public function scopeForOrganization(Builder $query, int $organizationId): void
    {
        $query->where('organization_id', $organizationId);
    }

    /**
     * Bootstrap the model and set default balance if omitted.
     */
    protected static function booted(): void
    {
        static::creating(function (Penalty $penalty): void {
            if ($penalty->paid_amount === null) {
                $penalty->paid_amount = 0;
            }

            if ($penalty->balance_remaining === null) {
                $penalty->balance_remaining = max(0, round((float) $penalty->amount - (float) $penalty->paid_amount, 2));
            }

            if ($penalty->status === null) {
                $penalty->status = PenaltyStatus::Unpaid;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'              => PenaltyType::class,
            'status'            => PenaltyStatus::class,
            'amount'            => 'decimal:2',
            'paid_amount'       => 'decimal:2',
            'balance_remaining' => 'decimal:2',
        ];
    }
}
