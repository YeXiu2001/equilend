<?php

declare(strict_types=1);

namespace App\Enums;

enum PenaltyType: string
{
    case Overdue = 'overdue';
    case Damage  = 'damage';
    case Loss    = 'loss';
    case Other   = 'other';

    /**
     * Get the human-readable label for the penalty type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Overdue => 'Overdue',
            self::Damage  => 'Damage',
            self::Loss    => 'Loss',
            self::Other   => 'Other',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this penalty type.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Overdue => 'badge-warning',
            self::Damage  => 'badge-error',
            self::Loss    => 'badge-error badge-outline',
            self::Other   => 'badge-neutral',
        };
    }
}
