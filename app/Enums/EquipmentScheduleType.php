<?php

declare(strict_types=1);

namespace App\Enums;

enum EquipmentScheduleType: string
{
    case Maintenance = 'maintenance';
    case Reservation = 'reservation';
    case Blackout    = 'blackout';

    /**
     * Get the human-readable label for the schedule type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'Maintenance',
            self::Reservation => 'Reservation',
            self::Blackout    => 'Blackout Period',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this schedule type.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Maintenance => 'badge-warning',
            self::Reservation => 'badge-info',
            self::Blackout    => 'badge-error',
        };
    }
}
