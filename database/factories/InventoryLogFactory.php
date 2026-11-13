<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\ItemUnit;
use App\Models\InventoryLog;
use App\Models\Organization;
use App\Enums\InventoryLogType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLog>
 */
class InventoryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id'         => Item::factory(),
            'item_unit_id'    => null,
            'organization_id' => function (array $attributes) {
                if (isset($attributes['item_id'])) {
                    $item = Item::find($attributes['item_id']);

                    if ($item !== null) {
                        return $item->organization_id;
                    }
                }

                return Organization::factory();
            },
            'user_id'          => User::factory(),
            'type'             => InventoryLogType::Restock,
            'quantity_change'  => fake()->numberBetween(1, 10),
            'reference_number' => mb_strtoupper(fake()->bothify('PO-####-????')),
            'notes'            => 'Initial batch stock intake and verification.',
        ];
    }

    /**
     * Indicate restock log.
     */
    public function restock(int $quantity = 5, ?string $referenceNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type'             => InventoryLogType::Restock,
            'quantity_change'  => $quantity,
            'reference_number' => $referenceNumber ?? mb_strtoupper(fake()->bothify('PO-####-????')),
            'notes'            => 'Warehouse delivery intake batch.',
        ]);
    }

    /**
     * Indicate audit adjustment log.
     */
    public function auditAdjustment(int $quantityChange = -1, ?string $referenceNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type'             => InventoryLogType::AuditAdjustment,
            'quantity_change'  => $quantityChange,
            'reference_number' => $referenceNumber ?? mb_strtoupper(fake()->bothify('AUD-####-????')),
            'notes'            => 'Discrepancy adjustment following physical inventory reconciliation.',
        ]);
    }

    /**
     * Indicate damage write-off log.
     */
    public function damageWriteoff(int $quantityChange = -1, ?string $referenceNumber = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type'             => InventoryLogType::DamageWriteoff,
            'quantity_change'  => $quantityChange,
            'reference_number' => $referenceNumber ?? mb_strtoupper(fake()->bothify('WRT-####-????')),
            'notes'            => 'Damaged beyond repair write-off from active lending circulation.',
        ]);
    }

    /**
     * Indicate status change log.
     */
    public function statusChange(?string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type'             => InventoryLogType::StatusChange,
            'quantity_change'  => 0,
            'reference_number' => mb_strtoupper(fake()->bothify('STC-####-????')),
            'notes'            => $notes ?? 'Unit status updated for maintenance servicing.',
        ]);
    }

    /**
     * Assign log to a specific item.
     */
    public function forItem(Item|int $item): static
    {
        $itemModel = $item instanceof Item ? $item : Item::findOrFail($item);

        return $this->state(fn (array $attributes) => [
            'item_id'         => $itemModel->id,
            'organization_id' => $itemModel->organization_id,
        ]);
    }

    /**
     * Assign log to a specific physical unit.
     */
    public function forUnit(ItemUnit|int $unit): static
    {
        $unitModel = $unit instanceof ItemUnit ? $unit : ItemUnit::findOrFail($unit);

        return $this->state(fn (array $attributes) => [
            'item_id'         => $unitModel->item_id,
            'item_unit_id'    => $unitModel->id,
            'organization_id' => $unitModel->organization_id,
        ]);
    }
}
