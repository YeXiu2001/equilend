<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use App\Enums\EquipmentScheduleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\EquipmentScheduleFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentSchedule extends Model
{
    /** @use HasFactory<EquipmentScheduleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'item_id',
        'item_unit_id',
        'title',
        'reason',
        'start_time',
        'end_time',
        'type',
    ];

    /**
     * Organization (college) associated with this schedule.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Specific equipment item undergoing maintenance or blackout (nullable if college-wide).
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Specific physical unit undergoing maintenance or reservation (nullable).
     *
     * @return BelongsTo<ItemUnit, $this>
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class);
    }

    /**
     * Check if the schedule entry is a maintenance window.
     */
    public function isMaintenance(): bool
    {
        return $this->type === EquipmentScheduleType::Maintenance;
    }

    /**
     * Check if the schedule entry is a blackout period.
     */
    public function isBlackout(): bool
    {
        return $this->type === EquipmentScheduleType::Blackout;
    }

    /**
     * Check if the schedule entry is an approved reservation block.
     */
    public function isReservation(): bool
    {
        return $this->type === EquipmentScheduleType::Reservation;
    }

    /**
     * Scope a query to entries for a specific organization.
     *
     * @param  Builder<EquipmentSchedule>  $query
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): void
    {
        $orgId = is_int($organization) ? $organization : $organization->id;
        $query->where('organization_id', $orgId);
    }

    /**
     * Scope a query to only include maintenance windows.
     *
     * @param  Builder<EquipmentSchedule>  $query
     */
    public function scopeMaintenance(Builder $query): void
    {
        $query->where('type', EquipmentScheduleType::Maintenance);
    }

    /**
     * Scope a query to only include blackout periods.
     *
     * @param  Builder<EquipmentSchedule>  $query
     */
    public function scopeBlackout(Builder $query): void
    {
        $query->where('type', EquipmentScheduleType::Blackout);
    }

    /**
     * Scope a query to only include reservation blocks.
     *
     * @param  Builder<EquipmentSchedule>  $query
     */
    public function scopeReservation(Builder $query): void
    {
        $query->where('type', EquipmentScheduleType::Reservation);
    }

    /**
     * Scope a query to events overlapping a specific time window.
     *
     * @param  Builder<EquipmentSchedule>  $query
     */
    public function scopeOverlapping(Builder $query, DateTimeInterface $start, DateTimeInterface $end): void
    {
        $query->where(function (Builder $subQuery) use ($start, $end): void {
            $subQuery->whereBetween('start_time', [$start, $end])
                ->orWhereBetween('end_time', [$start, $end])
                ->orWhere(function (Builder $inner) use ($start, $end): void {
                    $inner->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                });
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
            'start_time' => 'datetime',
            'end_time'   => 'datetime',
            'type'       => EquipmentScheduleType::class,
        ];
    }
}
