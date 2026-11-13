<?php

declare(strict_types=1);

namespace App\Enums;

enum LoanStatus: string
{
    case Active   = 'active';
    case Returned = 'returned';
    case Overdue  = 'overdue';
    case Lost     = 'lost';
    case Damaged  = 'damaged';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active   => 'Active',
            self::Returned => 'Returned',
            self::Overdue  => 'Overdue',
            self::Lost     => 'Lost',
            self::Damaged  => 'Damaged',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Active              => 'badge-primary',
            self::Returned            => 'badge-success',
            self::Overdue, self::Lost => 'badge-error',
            self::Damaged             => 'badge-warning',
        };
    }
}
