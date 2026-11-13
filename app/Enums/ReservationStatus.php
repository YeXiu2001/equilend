<?php

declare(strict_types=1);

namespace App\Enums;

enum ReservationStatus: string
{
    case Pending    = 'pending';
    case Approved   = 'approved';
    case Rejected   = 'rejected';
    case Cancelled  = 'cancelled';
    case CheckedOut = 'checked_out';
    case Expired    = 'expired';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Approved   => 'Approved',
            self::Rejected   => 'Rejected',
            self::Cancelled  => 'Cancelled',
            self::CheckedOut => 'Checked Out',
            self::Expired    => 'Expired',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending    => 'badge-warning',
            self::Approved   => 'badge-info',
            self::Rejected   => 'badge-error',
            self::Cancelled  => 'badge-neutral',
            self::CheckedOut => 'badge-primary',
            self::Expired    => 'badge-neutral',
        };
    }
}
