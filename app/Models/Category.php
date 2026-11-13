<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Str;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * Organization that this category belongs to, if college-scoped.
     *
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Items listed under this category.
     *
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Determine if this category is a global campus-wide category.
     */
    public function isGlobal(): bool
    {
        return $this->organization_id === null;
    }

    /**
     * Scope a query to only include global categories.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeGlobal(Builder $query): void
    {
        $query->whereNull('organization_id');
    }

    /**
     * Scope a query to include categories for a specific organization.
     *
     * @param  Builder<Category>  $query
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): void
    {
        $orgId = is_int($organization) ? $organization : $organization->id;

        $query->where('organization_id', $orgId);
    }

    /**
     * Scope a query to include categories available for a specific organization (global + college-scoped).
     *
     * @param  Builder<Category>  $query
     */
    public function scopeAvailableFor(Builder $query, int|Organization|null $organization = null): void
    {
        $query->where(function (Builder $builder) use ($organization): void {
            $builder->whereNull('organization_id');

            if ($organization !== null) {
                $orgId = is_int($organization) ? $organization : $organization->id;
                $builder->orWhere('organization_id', $orgId);
            }
        });
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if (empty($category->slug) && ! empty($category->name)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
