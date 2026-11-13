<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Organization;
use App\Models\EquipmentSchedule;
use App\Enums\EquipmentScheduleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentSchedule>
 */
class EquipmentScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'item_id'         => null,
            'item_unit_id'    => null,
            'title'           => 'Scheduled Equipment Calibration & Maintenance',
            'reason'          => 'Periodic preventive inspection, lens cleaning, and firmware upgrade.',
            'start_time'      => fake()->dateTimeBetween('+1 days', '+3 days'),
            'end_time'        => fake()->dateTimeBetween('+4 days', '+6 days'),
            'type'            => EquipmentScheduleType::Maintenance,
        ];
    }

    /**
     * Set type to maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'   => EquipmentScheduleType::Maintenance,
            'title'  => 'Preventive Maintenance Window',
            'reason' => 'Routine sensor cleaning and factory diagnostic testing.',
        ]);
    }

    /**
     * Set type to blackout period.
     */
    public function blackout(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'         => EquipmentScheduleType::Blackout,
            'item_id'      => null,
            'item_unit_id' => null,
            'title'        => 'College Midterm Examination Blackout',
            'reason'       => 'All circulation desk checkouts and reservations suspended during university exam week.',
        ]);
    }

    /**
     * Set type to reservation.
     */
    public function reservation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type'   => EquipmentScheduleType::Reservation,
            'title'  => 'Campus Academic Conference Exhibit',
            'reason' => 'Equipment reserved for official collegiate symposium.',
        ]);
    }

    /**
     * Scope to a specific organization.
     */
    public function forOrganization(Organization|int $organization): static
    {
        $orgId = is_int($organization) ? $organization : $organization->id;

        return $this->state(fn (array $attributes) => [
            'organization_id' => $orgId,
        ]);
    }

    /**
     * Scope to a specific catalog item.
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
     * Scope to a specific physical unit.
     */
    public function forUnit(ItemUnit|int $unit): static
    {
        $unitModel = $unit instanceof ItemUnit ? $unit : ItemUnit::findOrFail($unit);

        return $this->state(fn (array $attributes) => [
            'item_unit_id'    => $unitModel->id,
            'item_id'         => $unitModel->item_id,
            'organization_id' => $unitModel->organization_id,
        ]);
    }
}
