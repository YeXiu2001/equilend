<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Verified            = 'verified';
    case PendingVerification = 'pending_verification';
    case Voided              = 'voided';

    /**
     * Get the human-readable label for the payment status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Verified            => 'Verified',
            self::PendingVerification => 'Pending Verification',
            self::Voided              => 'Voided',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Verified            => 'badge-success',
            self::PendingVerification => 'badge-warning',
            self::Voided              => 'badge-ghost',
        };
    }
}
