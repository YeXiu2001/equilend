<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemUnitStatus: string
{
    case Available        = 'available';
    case Reserved         = 'reserved';
    case Borrowed         = 'borrowed';
    case UnderMaintenance = 'under_maintenance';
    case Damaged          = 'damaged';
    case Lost             = 'lost';
    case Retired          = 'retired';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Available        => 'Available',
            self::Reserved         => 'Reserved',
            self::Borrowed         => 'Borrowed',
            self::UnderMaintenance => 'Under Maintenance',
            self::Damaged          => 'Damaged',
            self::Lost             => 'Lost',
            self::Retired          => 'Retired',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available                          => 'badge-success',
            self::Reserved                           => 'badge-info',
            self::Borrowed                           => 'badge-primary',
            self::UnderMaintenance                   => 'badge-warning',
            self::Damaged, self::Lost, self::Retired => 'badge-error',
        };
    }
}
