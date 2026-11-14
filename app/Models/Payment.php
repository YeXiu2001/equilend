<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use App\Enums\PaymentStatus;
use App\Enums\PenaltyStatus;
use Illuminate\Support\Facades\DB;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'penalty_id',
        'user_id',
        'payment_method_id',
        'organization_payment_method_id',
        'amount_paid',
        'reference_number',
        'receipt_number',
        'payment_proof_url',
        'paid_at',
        'status',
        'received_by',
        'notes',
    ];

    /**
     * Generate a unique sequential official receipt number (e.g. RCP-2026-0001).
     */
    public static function generateReceiptNumber(): string
    {
        $year   = now()->format('Y');
        $prefix = "RCP-{$year}-";

        $latest = static::where('receipt_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('receipt_number');

        $nextNumber = 1;

        if ($latest !== null) {
            $suffix     = (int) mb_substr($latest, mb_strlen($prefix));
            $nextNumber = $suffix + 1;
        }

        do {
            $candidate = $prefix.mb_str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $exists    = static::where('receipt_number', $candidate)->exists();
            $nextNumber++;
        } while ($exists);

        return $candidate;
    }

    /**
     * Organization (college) that received this payment.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Penalty settled by this payment.
     *
     * @return BelongsTo<Penalty, $this>
     */
    public function penalty(): BelongsTo
    {
        return $this->belongsTo(Penalty::class);
    }

    /**
     * Borrower (student or faculty member) who made the payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for payer relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Staff or admin user who collected/verified this payment.
     *
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Global payment channel (Cash, GCash, Maya, etc.).
     *
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Organization-specific payment account configuration (nullable for cash).
     *
     * @return BelongsTo<OrganizationPaymentMethod, $this>
     */
    public function organizationPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(OrganizationPaymentMethod::class);
    }

    /**
     * Get the public URL for the uploaded payment proof image.
     */
    public function getProofUrlAttribute(): ?string
    {
        if (! $this->payment_proof_url) {
            return null;
        }

        if (filter_var($this->payment_proof_url, FILTER_VALIDATE_URL)) {
            return $this->payment_proof_url;
        }

        return Storage::disk('public')->url($this->payment_proof_url);
    }

    /**
     * Get all pending verification payments submitted together with this receipt proof.
     *
     * @return Collection<int, Payment>
     */
    public function siblingPendingPayments(): Collection
    {
        if (! $this->payment_proof_url) {
            return new Collection($this->status === PaymentStatus::PendingVerification ? [$this] : []);
        }

        return static::query()
            ->with('penalty')
            ->where('user_id', $this->user_id)
            ->where('payment_proof_url', $this->payment_proof_url)
            ->where('status', PaymentStatus::PendingVerification)
            ->get();
    }

    /**
     * Verify a pending payment, applying its amount to the associated penalty.
     *
     * @throws DomainException
     */
    public function verify(int $staffId, ?string $notes = null): void
    {
        DB::transaction(function () use ($staffId, $notes): void {
            $payment = static::lockForUpdate()->findOrFail($this->id);

            if ($payment->status !== PaymentStatus::PendingVerification) {
                throw new DomainException("Cannot verify a payment with status {$payment->status->label()}.");
            }

            $penalty = Penalty::lockForUpdate()->findOrFail($payment->penalty_id);

            if (in_array($penalty->status, [PenaltyStatus::Paid, PenaltyStatus::Waived, PenaltyStatus::Voided], true)) {
                throw new DomainException("Cannot apply payment to a penalty with status {$penalty->status->label()}.");
            }

            $amount         = round((float) $payment->amount_paid, 2);
            $currentBalance = round((float) $penalty->balance_remaining, 2);

            if (abs($amount - $currentBalance) >= 0.01) {
                throw new DomainException("Partial payments and overpayments are not allowed. Payment amount ({$amount}) must equal the full remaining balance ({$currentBalance}).");
            }

            $newPaidAmount = round((float) $penalty->paid_amount + $currentBalance, 2);

            $penalty->paid_amount       = $newPaidAmount;
            $penalty->balance_remaining = 0.0;
            $penalty->status            = PenaltyStatus::Paid;
            $penalty->save();

            $payment->status      = PaymentStatus::Verified;
            $payment->received_by = $staffId;

            if ($notes !== null && mb_trim($notes) !== '') {
                $payment->notes = mb_trim($notes);
            }

            $payment->save();

            $this->refresh();
        });
    }

    /**
     * Reject (void) a pending verification payment without altering the penalty balance.
     *
     * @throws DomainException
     */
    public function reject(int $staffId, ?string $reason = null): void
    {
        DB::transaction(function () use ($staffId, $reason): void {
            $payment = static::lockForUpdate()->findOrFail($this->id);

            if ($payment->status !== PaymentStatus::PendingVerification) {
                throw new DomainException("Cannot reject a payment with status {$payment->status->label()}.");
            }

            $payment->status      = PaymentStatus::Voided;
            $payment->received_by = $staffId;

            if ($reason !== null && mb_trim($reason) !== '') {
                $payment->notes = 'Rejected: '.mb_trim($reason);
            }

            $payment->save();

            $this->refresh();
        });
    }

    /**
     * Scope query to a specific organization.
     *
     * @param  Builder<Payment>  $query
     */
    public function scopeForOrganization(Builder $query, int $organizationId): void
    {
        $query->where('organization_id', $organizationId);
    }

    /**
     * Bootstrap the model to auto-generate unique receipt numbers.
     */
    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = static::generateReceiptNumber();
            }

            if ($payment->paid_at === null) {
                $payment->paid_at = now();
            }

            if ($payment->status === null) {
                $payment->status = PaymentStatus::Verified;
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
            'status'      => PaymentStatus::class,
            'amount_paid' => 'decimal:2',
            'paid_at'     => 'datetime',
        ];
    }
}
