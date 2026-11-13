<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use Database\Factories\ItemUnitFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemUnit extends Model
{
    /** @use HasFactory<ItemUnitFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'organization_id',
        'asset_tag',
        'serial_number',
        'condition',
        'status',
        'notes',
    ];

    /**
     * Item catalog entry that this unit belongs to.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Organization that owns this unit.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Inventory logs associated with this specific physical unit.
     *
     * @return HasMany<InventoryLog, $this>
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Reservations that were allocated this specific physical unit.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Loans of this specific physical unit.
     *
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Active or overdue loan currently holding this unit.
     *
     * @return HasOne<Loan, $this>
     */
    public function activeLoan(): HasOne
    {
        return $this->hasOne(Loan::class)->whereIn('status', [LoanStatus::Active, LoanStatus::Overdue]);
    }

    /**
     * Equipment schedules (maintenance/blackout) assigned to this unit.
     *
     * @return HasMany<EquipmentSchedule, $this>
     */
    public function equipmentSchedules(): HasMany
    {
        return $this->hasMany(EquipmentSchedule::class);
    }

    /**
     * Check if the unit is currently available for checkout or reservation.
     */
    public function isAvailable(): bool
    {
        return $this->status === ItemUnitStatus::Available;
    }

    /**
     * Check if the unit is under maintenance.
     */
    public function isUnderMaintenance(): bool
    {
        return $this->status === ItemUnitStatus::UnderMaintenance;
    }

    /**
     * Check if the unit is damaged.
     */
    public function isDamaged(): bool
    {
        return $this->status === ItemUnitStatus::Damaged;
    }

    /**
     * Scope a query to only include available units.
     *
     * @param  Builder<ItemUnit>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', ItemUnitStatus::Available);
    }

    /**
     * Scope a query to only include units under maintenance.
     *
     * @param  Builder<ItemUnit>  $query
     */
    public function scopeUnderMaintenance(Builder $query): void
    {
        $query->where('status', ItemUnitStatus::UnderMaintenance);
    }

    /**
     * Scope a query to only include damaged units.
     *
     * @param  Builder<ItemUnit>  $query
     */
    public function scopeDamaged(Builder $query): void
    {
        $query->where('status', ItemUnitStatus::Damaged);
    }

    /**
     * Scope a query to include units for a specific organization.
     *
     * @param  Builder<ItemUnit>  $query
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): void
    {
        $orgId = is_int($organization) ? $organization : $organization->id;

        $query->where('organization_id', $orgId);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'condition' => ItemUnitCondition::class,
            'status'    => ItemUnitStatus::class,
        ];
    }
}
