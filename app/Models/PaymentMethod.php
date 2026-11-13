<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\PaymentMethodFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PaymentMethod extends Model
{
    /** @use HasFactory<PaymentMethodFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'payment_code',
        'name',
        'description',
        'is_active',
    ];

    /**
     * Organizations that use this payment method.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_payment_methods')
            ->withPivot(['account_name', 'account_numbers', 'is_enabled'])
            ->withTimestamps();
    }

    /**
     * Direct relationship to organization payment method configurations.
     *
     * @return HasMany<OrganizationPaymentMethod, $this>
     */
    public function organizationPaymentMethods(): HasMany
    {
        return $this->hasMany(OrganizationPaymentMethod::class);
    }

    /**
     * Scope a query to only include active payment methods.
     *
     * @param  Builder<PaymentMethod>  $query
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
