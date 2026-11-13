<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InventoryLogType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\InventoryLogFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryLog extends Model
{
    /** @use HasFactory<InventoryLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'item_unit_id',
        'organization_id',
        'user_id',
        'type',
        'quantity_change',
        'reference_number',
        'notes',
    ];

    /**
     * Equipment catalog item related to this log.
     *
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Specific physical unit related to this log (if applicable).
     *
     * @return BelongsTo<ItemUnit, $this>
     */
    public function itemUnit(): BelongsTo
    {
        return $this->belongsTo(ItemUnit::class);
    }

    /**
     * Organization that owns this inventory ledger record.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * User / staff member who recorded this stock movement.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to include logs for a specific organization.
     *
     * @param  Builder<InventoryLog>  $query
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): void
    {
        $orgId = is_int($organization) ? $organization : $organization->id;

        $query->where('organization_id', $orgId);
    }

    /**
     * Scope a query to include logs for a specific movement type.
     *
     * @param  Builder<InventoryLog>  $query
     */
    public function scopeForType(Builder $query, InventoryLogType|string $type): void
    {
        $value = $type instanceof InventoryLogType ? $type->value : $type;

        $query->where('type', $value);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'            => InventoryLogType::class,
            'quantity_change' => 'integer',
        ];
    }
}
