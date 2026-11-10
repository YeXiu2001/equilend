<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
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
