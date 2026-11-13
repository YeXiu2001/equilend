<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemUnit>
 */
class ItemUnitFactory extends Factory
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
            'organization_id' => function (array $attributes) {
                if (isset($attributes['item_id'])) {
                    $item = Item::find($attributes['item_id']);

                    if ($item !== null) {
                        return $item->organization_id;
                    }
                }

                return Organization::factory();
            },
            'asset_tag'     => mb_strtoupper(fake()->unique()->bothify('TAG-####-????')),
            'serial_number' => mb_strtoupper(fake()->bothify('SN-########')),
            'condition'     => ItemUnitCondition::Good,
            'status'        => ItemUnitStatus::Available,
            'notes'         => null,
        ];
    }

    /**
     * Set status to available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ItemUnitStatus::Available,
        ]);
    }

    /**
     * Set status to under maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ItemUnitStatus::UnderMaintenance,
            'notes'  => 'Scheduled preventive inspection or routine servicing.',
        ]);
    }

    /**
     * Set status to damaged.
     */
    public function damaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'    => ItemUnitStatus::Damaged,
            'condition' => ItemUnitCondition::Poor,
            'notes'     => 'Reported hardware defect or cosmetic damage requiring repair.',
        ]);
    }

    /**
     * Set status to borrowed.
     */
    public function borrowed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ItemUnitStatus::Borrowed,
        ]);
    }

    /**
     * Set status to reserved.
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ItemUnitStatus::Reserved,
        ]);
    }

    /**
     * Assign unit to a specific item.
     */
    public function forItem(Item|int $item): static
    {
        $itemModel = $item instanceof Item ? $item : Item::findOrFail($item);

        return $this->state(fn (array $attributes) => [
            'item_id'         => $itemModel->id,
            'organization_id' => $itemModel->organization_id,
        ]);
    }
}
