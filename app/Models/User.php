<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LoanStatus;
use App\Enums\PenaltyStatus;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'pic_dir',
    ];

    protected $appends = [
        'pic_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    /**
     * The organizations that the user belongs to.
     *
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The single organization that this user belongs to.
     */
    public function organization(): ?Organization
    {
        return $this->organizations()->first();
    }

    /**
     * Get organization attribute.
     */
    public function getOrganizationAttribute(): ?Organization
    {
        return $this->organizations->first();
    }

    /**
     * Check if user is assigned to an organization.
     */
    public function hasOrganization(): bool
    {
        return $this->organizations()->exists();
    }

    /**
     * Inventory logs created or authorized by this user.
     *
     * @return HasMany<InventoryLog, $this>
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Reservations placed by this user as borrower.
     *
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'user_id');
    }

    /**
     * Loans borrowed by this user.
     *
     * @return HasMany<Loan, $this>
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'user_id');
    }

    /**
     * Loans issued by this user as circulation desk staff.
     *
     * @return HasMany<Loan, $this>
     */
    public function issuedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'issued_by');
    }

    /**
     * Loans received back by this user as circulation desk staff.
     *
     * @return HasMany<Loan, $this>
     */
    public function receivedLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'received_by');
    }

    /**
     * Reservations approved or rejected by this user as administrator.
     *
     * @return HasMany<Reservation, $this>
     */
    public function approvedReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'approved_by');
    }

    /**
     * Penalties assessed against this user.
     *
     * @return HasMany<Penalty, $this>
     */
    public function penalties(): HasMany
    {
        return $this->hasMany(Penalty::class, 'user_id');
    }

    /**
     * Payments made by this user toward penalties.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id');
    }

    /**
     * Sum of remaining balances across all unsettled penalties.
     */
    public function getOutstandingFinesTotal(): float
    {
        return round((float) $this->penalties()
            ->whereIn('status', [PenaltyStatus::Unpaid, PenaltyStatus::PartiallyPaid])
            ->where('balance_remaining', '>', 0)
            ->sum('balance_remaining'), 2);
    }

    /**
     * Determine whether the user is eligible to borrow or reserve equipment.
     */
    public function canBorrow(): bool
    {
        $hasOverdueLoans = $this->loans()
            ->where(function ($query): void {
                $query->where('status', LoanStatus::Overdue)
                    ->orWhere(function ($q): void {
                        $q->where('status', LoanStatus::Active)
                            ->whereNull('returned_at')
                            ->where('due_at', '<', now());
                    });
            })
            ->exists();

        if ($hasOverdueLoans) {
            return false;
        }

        return $this->getOutstandingFinesTotal() <= 0.0;
    }

    /**
     * Get human-readable reasons explaining why borrowing is blocked.
     *
     * @return list<string>
     */
    public function getBorrowingBlockReasons(): array
    {
        $reasons = [];

        $overdueCount = $this->loans()
            ->where(function ($query): void {
                $query->where('status', LoanStatus::Overdue)
                    ->orWhere(function ($q): void {
                        $q->where('status', LoanStatus::Active)
                            ->whereNull('returned_at')
                            ->where('due_at', '<', now());
                    });
            })
            ->count();

        if ($overdueCount > 0) {
            $reasons[] = "{$overdueCount} overdue equipment loan(s) must be returned first.";
        }

        $outstandingTotal = $this->getOutstandingFinesTotal();

        if ($outstandingTotal > 0.0) {
            $formatted = number_format($outstandingTotal, 2);
            $reasons[] = "Unsettled penalty balance of ₱{$formatted} must be cleared.";
        }

        return $reasons;
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function getPicUrlAttribute(): ?string
    {
        if (! $this->pic_dir) {
            return null;
        }

        if (filter_var($this->pic_dir, FILTER_VALIDATE_URL)) {
            return $this->pic_dir;
        }

        return Storage::disk('public')->url($this->pic_dir);
    }
}
