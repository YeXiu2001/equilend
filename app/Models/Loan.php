<?php

declare(strict_types=1);

namespace App\Models;

use DomainException;
use App\Enums\LoanStatus;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use App\Enums\ReservationStatus;
use Illuminate\Support\Facades\DB;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'reservation_id',
        'user_id',
        'item_id',
        'item_unit_id',
        'issued_by',
        'received_by',
        'borrowed_at',
        'due_at',
        'returned_at',
        'reminder_sent_at',
        'checkout_condition',
        'checkin_condition',
        'status',
        'notes',
    ];

    /**
     * Perform an equipment checkout with transaction safety and unit synchronization.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws DomainException
     */
    public static function checkOut(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $unitId = $attributes['item_unit_id'] ?? null;
            $unit   = ItemUnit::lockForUpdate()->findOrFail($unitId);

            $unavailableStatuses = [
                ItemUnitStatus::UnderMaintenance,
                ItemUnitStatus::Damaged,
                ItemUnitStatus::Lost,
                ItemUnitStatus::Borrowed,
                ItemUnitStatus::Retired,
            ];

            if (in_array($unit->status, $unavailableStatuses, true)) {
                throw new DomainException("Item unit {$unit->asset_tag} cannot be checked out because it is currently {$unit->status->label()}.");
            }

            $hasActiveLoan = static::where('item_unit_id', $unit->id)
                ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
                ->exists();

            if ($hasActiveLoan) {
                throw new DomainException("Item unit {$unit->asset_tag} is already checked out on an active loan.");
            }

            $itemId            = $attributes['item_id'] ?? $unit->item_id;
            $orgId             = $attributes['organization_id'] ?? $unit->organization_id;
            $checkoutCondition = $attributes['checkout_condition'] ?? $unit->condition;

            $loan = static::create([
                'organization_id'    => $orgId,
                'reservation_id'     => $attributes['reservation_id'] ?? null,
                'user_id'            => $attributes['user_id'],
                'item_id'            => $itemId,
                'item_unit_id'       => $unit->id,
                'issued_by'          => $attributes['issued_by'],
                'received_by'        => null,
                'borrowed_at'        => $attributes['borrowed_at'] ?? now(),
                'due_at'             => $attributes['due_at'],
                'returned_at'        => null,
                'checkout_condition' => $checkoutCondition,
                'checkin_condition'  => null,
                'status'             => LoanStatus::Active,
                'notes'              => $attributes['notes'] ?? null,
            ]);

            $unit->update(['status' => ItemUnitStatus::Borrowed]);

            if (! empty($attributes['reservation_id'])) {
                $reservation = Reservation::find($attributes['reservation_id']);

                if ($reservation !== null) {
                    $reservation->update([
                        'status'       => ReservationStatus::CheckedOut,
                        'item_unit_id' => $unit->id,
                    ]);
                }
            }

            return $loan;
        });
    }

    /**
     * Organization (college) where the loan transaction belongs.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Linked reservation if checked out from an approved reservation request.
     *
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Borrower (student or faculty member).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Alias for borrower relationship.
     *
     * @return BelongsTo<User, $this>
     */
    public function borrower(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Circulation desk staff who issued the physical unit.
     *
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Circulation desk staff who received the returned physical unit.
     *
     * @return BelongsTo<User, $this>
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Catalog item loaned out.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Physical serialized item unit assigned to this loan.
     *
     * @return BelongsTo<ItemUnit, $this>
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class);
    }

    /**
     * Penalties assessed in relation to this loan.
     *
     * @return HasMany<Penalty, $this>
     */
    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    /**
     * Check if the loan is currently overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->status === LoanStatus::Overdue) {
            return true;
        }

        return $this->status === LoanStatus::Active
            && $this->returned_at === null
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    /**
     * Mark the loan as returned, inspect condition, and synchronize unit status.
     */
    public function markAsReturned(User|int $receiver, ItemUnitCondition|string|null $condition = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($receiver, $condition, $notes) {
            $receiverId = is_int($receiver) ? $receiver : $receiver->id;

            $resolvedCondition = $condition instanceof ItemUnitCondition
                ? $condition
                : ($condition !== null ? ItemUnitCondition::from($condition) : $this->checkout_condition);

            $isDamaged = ($resolvedCondition === ItemUnitCondition::Poor);

            $this->update([
                'received_by'       => $receiverId,
                'returned_at'       => now(),
                'checkin_condition' => $resolvedCondition,
                'status'            => $isDamaged ? LoanStatus::Damaged : LoanStatus::Returned,
                'notes'             => $notes ?? $this->notes,
            ]);

            $newUnitStatus = $isDamaged ? ItemUnitStatus::Damaged : ItemUnitStatus::Available;
            $this->itemUnit->update([
                'status'    => $newUnitStatus,
                'condition' => $resolvedCondition,
            ]);
        });
    }

    /**
     * Mark the loan as lost and update the physical unit status.
     */
    public function markAsLost(?string $notes = null): void
    {
        DB::transaction(function () use ($notes) {
            $this->update([
                'status' => LoanStatus::Lost,
                'notes'  => $notes ?? $this->notes,
            ]);

            $this->itemUnit->update([
                'status' => ItemUnitStatus::Lost,
            ]);
        });
    }

    /**
     * Mark the loan as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->update(['status' => LoanStatus::Overdue]);
    }

    /**
     * Scope a query to only include active loans.
     *
     * @param  Builder<Loan>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', LoanStatus::Active);
    }

    /**
     * Scope a query to include loans that are overdue (either marked overdue or past due date).
     *
     * @param  Builder<Loan>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where(function (Builder $subQuery): void {
            $subQuery->where('status', LoanStatus::Overdue)
                ->orWhere(function (Builder $inner): void {
                    $inner->where('status', LoanStatus::Active)
                        ->whereNull('returned_at')
                        ->where('due_at', '<', now());
                });
        });
    }

    /**
     * Scope a query to only include returned loans (including damaged upon return).
     *
     * @param  Builder<Loan>  $query
     */
    public function scopeReturned(Builder $query): void
    {
        $query->whereIn('status', [LoanStatus::Returned, LoanStatus::Damaged])
            ->whereNotNull('returned_at');
    }

    /**
     * Scope a query to loans for a specific organization.
     *
     * @param  Builder<Loan>  $query
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): void
    {
        $orgId = is_int($organization) ? $organization : $organization->id;
        $query->where('organization_id', $orgId);
    }

    /**
     * Scope a query to loans for a specific borrower.
     *
     * @param  Builder<Loan>  $query
     */
    public function scopeForUser(Builder $query, int|User $user): void
    {
        $userId = is_int($user) ? $user : $user->id;
        $query->where('user_id', $userId);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Loan $loan): void {
            if ($loan->status === LoanStatus::Active) {
                $unit = $loan->itemUnit ?? ItemUnit::find($loan->item_unit_id);

                if ($unit !== null) {
                    $unavailableStatuses = [
                        ItemUnitStatus::UnderMaintenance,
                        ItemUnitStatus::Damaged,
                        ItemUnitStatus::Lost,
                        ItemUnitStatus::Borrowed,
                        ItemUnitStatus::Retired,
                    ];

                    if (in_array($unit->status, $unavailableStatuses, true)) {
                        throw new DomainException("Item unit {$unit->asset_tag} cannot be checked out because it is currently {$unit->status->label()}.");
                    }

                    $hasActiveLoan = static::where('item_unit_id', $unit->id)
                        ->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue])
                        ->exists();

                    if ($hasActiveLoan) {
                        throw new DomainException("Item unit {$unit->asset_tag} is already checked out on an active loan.");
                    }
                }
            }
        });

        static::created(function (Loan $loan): void {
            if ($loan->status === LoanStatus::Active || $loan->status === LoanStatus::Overdue) {
                $unit = $loan->itemUnit ?? ItemUnit::find($loan->item_unit_id);

                if ($unit !== null && $unit->status !== ItemUnitStatus::Borrowed) {
                    $unit->update(['status' => ItemUnitStatus::Borrowed]);
                }

                if ($loan->reservation_id) {
                    $reservation = $loan->reservation ?? Reservation::find($loan->reservation_id);

                    if ($reservation !== null && $reservation->status !== ReservationStatus::CheckedOut) {
                        $reservation->update([
                            'status'       => ReservationStatus::CheckedOut,
                            'item_unit_id' => $loan->item_unit_id,
                        ]);
                    }
                }
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
            'borrowed_at'        => 'datetime',
            'due_at'             => 'datetime',
            'returned_at'        => 'datetime',
            'reminder_sent_at'   => 'datetime',
            'checkout_condition' => ItemUnitCondition::class,
            'checkin_condition'  => ItemUnitCondition::class,
            'status'             => LoanStatus::class,
        ];
    }
}
