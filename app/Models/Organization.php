<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'description',
        'is_active',
    ];

    /**
     * Members belonging to this organization.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organization_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The primary assigned user/manager of this organization.
     */
    public function assignedUser(): ?User
    {
        return $this->members()->first();
    }

    /**
     * Get assigned user attribute.
     */
    public function getAssignedUserAttribute(): ?User
    {
        return $this->members->first();
    }

    /**
     * Configured payment methods for this organization.
     *
     * @return BelongsToMany<PaymentMethod, $this>
     */
    public function paymentMethods(): BelongsToMany
    {
        return $this->belongsToMany(PaymentMethod::class, 'organization_payment_methods')
            ->withPivot(['account_name', 'account_numbers', 'is_enabled'])
            ->withTimestamps();
    }

    /**
     * Direct relationship to payment method configurations.
     *
     * @return HasMany<OrganizationPaymentMethod, $this>
     */
    public function organizationPaymentMethods(): HasMany
    {
        return $this->hasMany(OrganizationPaymentMethod::class);
    }

    /**
     * Scope a query to only include active organizations.
     *
     * @param  Builder<Organization>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
