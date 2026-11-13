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
     * Categories defined specifically for this organization.
     *
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Equipment catalog items belonging to this organization.
     *
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Equipment physical units belonging to this organization.
     *
     * @return HasMany<ItemUnit, $this>
     */
    public function itemUnits(): HasMany
    {
        return $this->hasMany(ItemUnit::class);
    }

    /**
     * Inventory logs belonging to this organization.
     *
     * @return HasMany<InventoryLog, $this>
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Equipment reservations booked with this organization.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Equipment loans managed by this organization.
     *
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Equipment schedules and maintenance windows configured for this organization.
     *
     * @return HasMany<EquipmentSchedule, $this>
     */
    public function equipmentSchedules(): HasMany
    {
        return $this->hasMany(EquipmentSchedule::class);
    }

    /**
     * Penalties assessed under this organization.
     *
     * @return HasMany<Penalty, $this>
     */
    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class);
    }

    /**
     * Payments collected by this organization.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
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
