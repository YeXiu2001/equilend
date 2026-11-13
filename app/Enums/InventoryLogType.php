<?php

declare(strict_types=1);

namespace App\Enums;

enum InventoryLogType: string
{
    case Restock         = 'restock';
    case AuditAdjustment = 'audit_adjustment';
    case DamageWriteoff  = 'damage_writeoff';
    case StatusChange    = 'status_change';

    /**
     * Get the human-readable label for the inventory log type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Restock         => 'Restock',
            self::AuditAdjustment => 'Audit Adjustment',
            self::DamageWriteoff  => 'Damage Write-off',
            self::StatusChange    => 'Status Change',
        };
    }

    /**
     * Get the daisyUI semantic badge class for this log type.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Restock         => 'badge-success',
            self::AuditAdjustment => 'badge-info',
            self::DamageWriteoff  => 'badge-error',
            self::StatusChange    => 'badge-neutral',
        };
    }
}
