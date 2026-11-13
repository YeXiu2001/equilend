<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ItemUnitStatus;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'category_id',
        'name',
        'brand',
        'model_number',
        'description',
        'specifications',
        'image_url',
        'is_active',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'total_units_count',
        'available_units_count',
    ];

    /**
     * Organization that this item belongs to.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Category that this item belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Physical units of this item.
     *
     * @return HasMany<ItemUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    /**
     * Alias for units relationship.
     *
     * @return HasMany<ItemUnit, $this>
     */
    public function itemUnits(): HasMany
    {
        return $this->units();
    }

    /**
     * Inventory audit and restocking ledger logs.
     *
     * @return HasMany<InventoryLog, $this>
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Reservations placed for this catalog item.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Circulation loans of physical units belonging to this item.
     *
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Equipment schedule windows (maintenance/blackout) for this item.
     *
     * @return HasMany<EquipmentSchedule, $this>
     */
    public function equipmentSchedules(): HasMany
    {
        return $this->hasMany(EquipmentSchedule::class);
    }

    /**
     * Dynamic / cached total units counter.
     */
    public function getTotalUnitsCountAttribute(): int
    {
        if (isset($this->attributes['units_count'])) {
            return (int) $this->attributes['units_count'];
        }

        if (isset($this->attributes['total_units_count'])) {
            return (int) $this->attributes['total_units_count'];
        }

        if ($this->relationLoaded('units')) {
            return $this->units->count();
        }

        return $this->units()->count();
    }

    /**
     * Dynamic / cached available units counter.
     */
    public function getAvailableUnitsCountAttribute(): int
    {
        if (isset($this->attributes['available_units_count'])) {
            return (int) $this->attributes['available_units_count'];
        }

        if ($this->relationLoaded('units')) {
            return $this->units->where('status', ItemUnitStatus::Available)->count();
        }

        return $this->units()->where('status', ItemUnitStatus::Available)->count();
    }

    /**
     * Resolved image URL if stored locally or externally.
     */
    public function getResolvedImageUrlAttribute(): ?string
    {
        $image = $this->image_url;

        if (! $image) {
            return null;
        }

        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return Storage::disk('public')->url($image);
    }

    /**
     * Scope a query to only include active items.
     *
     * @param  Builder<Item>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope a query to include items for a specific organization.
     *
     * @param  Builder<Item>  $query
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
            'specifications' => 'array',
            'is_active'      => 'boolean',
        ];
    }
}
