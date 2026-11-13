<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemUnitCondition: string
{
    case New  = 'new';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    /**
     * Get the human-readable label for the condition.
     */
    public function label(): string
    {
        return match ($this) {
            self::New  => 'New',
            self::Good => 'Good',
            self::Fair => 'Fair',
            self::Poor => 'Poor',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this condition.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::New, self::Good => 'badge-success',
            self::Fair            => 'badge-warning',
            self::Poor            => 'badge-error',
        };
    }
}
