<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemUnitStatus;
use InvalidArgumentException;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'user_id',
        'item_id',
        'item_unit_id',
        'start_time',
        'end_time',
        'purpose',
        'status',
        'rejection_remarks',
        'approved_by',
        'approved_at',
    ];

    /**
     * Organization (college) where the reservation was booked.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Borrower (student or faculty member) who created the reservation.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for user relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function borrower(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Staff/admin user who approved or rejected this reservation.
     *
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Catalog item reserved.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Specific physical unit assigned to this reservation (nullable until allocated).
     *
     * @return BelongsTo<ItemUnit, $this>
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class);
    }

    /**
     * Resulting loan fulfilled from this reservation.
     *
     * @return HasOne<Loan, $this>
     */
    public function loan(): HasOne
    {
        return $this->hasOne(Loan::class);
    }

    /**
     * Check if the reservation is still pending review.
     */
    public function isPending(): bool
    {
        return $this->status === ReservationStatus::Pending;
    }

    /**
     * Check if the reservation has been approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ReservationStatus::Approved;
    }

    /**
     * Check if the reservation was rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === ReservationStatus::Rejected;
    }

    /**
     * Check if the reservation was cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === ReservationStatus::Cancelled;
    }

    /**
     * Check if the reservation was fulfilled into a checkout.
     */
    public function isCheckedOut(): bool
    {
        return $this->status === ReservationStatus::CheckedOut;
    }

    /**
     * Check if the reservation has expired without checkout.
     */
    public function isExpired(): bool
    {
        return $this->status === ReservationStatus::Expired;
    }

    /**
     * Approve the reservation, optionally binding a physical item unit.
     */
    public function approve(User|int $approver, ?ItemUnit $unit = null): void
    {
        $approverId = is_int($approver) ? $approver : $approver->id;

        $this->update([
            'status'       => ReservationStatus::Approved,
            'approved_by'  => $approverId,
            'approved_at'  => now(),
            'item_unit_id' => $unit?->id ?? $this->item_unit_id,
        ]);

        if ($unit !== null) {
            $unit->update(['status' => ItemUnitStatus::Reserved]);
        }
    }

    /**
     * Reject the reservation with mandatory remarks.
     *
     * @throws InvalidArgumentException
     */
    public function reject(User|int $approver, string $remarks): void
    {
        if (mb_trim($remarks) === '') {
            throw new InvalidArgumentException('Rejection remarks are mandatory when rejecting a reservation.');
        }

        $approverId = is_int($approver) ? $approver : $approver->id;

        $this->update([
            'status'            => ReservationStatus::Rejected,
            'approved_by'       => $approverId,
            'approved_at'       => now(),
            'rejection_remarks' => mb_trim($remarks),
        ]);
    }

    /**
     * Cancel the reservation.
     */
    public function cancel(): void
    {
        $this->update(['status' => ReservationStatus::Cancelled]);

        if ($this->item_unit_id && $this->itemUnit?->status === ItemUnitStatus::Reserved) {
            $this->itemUnit->update(['status' => ItemUnitStatus::Available]);
        }
    }

    /**
     * Mark the reservation as checked out.
     */
    public function markAsCheckedOut(): void
    {
        $this->update(['status' => ReservationStatus::CheckedOut]);
    }

    /**
     * Mark the reservation as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => ReservationStatus::Expired]);

        if ($this->item_unit_id && $this->itemUnit?->status === ItemUnitStatus::Reserved) {
            $this->itemUnit->update(['status' => ItemUnitStatus::Available]);
        }
    }

    /**
     * Scope a query to only include pending reservations.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ReservationStatus::Pending);
    }

    /**
     * Scope a query to only include approved reservations.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ReservationStatus::Approved);
    }

    /**
     * Scope a query to reservations for a specific organization.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): void
    {
        $orgId = is_int($organization) ? $organization : $organization->id;
        $query->where('organization_id', $orgId);
    }

    /**
     * Scope a query to reservations created by a specific user.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeForUser(Builder $query, int|User $user): void
    {
        $userId = is_int($user) ? $user : $user->id;
        $query->where('user_id', $userId);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time'  => 'datetime',
            'end_time'    => 'datetime',
            'approved_at' => 'datetime',
            'status'      => ReservationStatus::class,
        ];
    }
}
