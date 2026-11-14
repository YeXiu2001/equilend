<?php

declare(strict_types=1);

namespace App\Enums;

enum PenaltyStatus: string
{
    case PendingApproval = 'pending_approval';
    case Unpaid          = 'unpaid';
    case PartiallyPaid   = 'partially_paid';
    case Paid            = 'paid';
    case Waived          = 'waived';
    case Voided          = 'voided';

    /**
     * Get the human-readable label for the penalty status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingApproval => 'Pending Approval',
            self::Unpaid          => 'Unpaid',
            self::PartiallyPaid   => 'Partially Paid',
            self::Paid            => 'Paid',
            self::Waived          => 'Waived',
            self::Voided          => 'Voided',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingApproval => 'badge-warning',
            self::Unpaid          => 'badge-error',
            self::PartiallyPaid   => 'badge-warning',
            self::Paid            => 'badge-success',
            self::Waived          => 'badge-info',
            self::Voided          => 'badge-ghost',
        };
    }
}
