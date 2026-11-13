<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\User;
use App\Models\ItemUnit;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
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
            'user_id'      => User::factory(),
            'item_unit_id' => null,
            'start_time'   => fake()->dateTimeBetween('+1 days', '+3 days'),
            'end_time'     => fake()->dateTimeBetween('+4 days', '+7 days'),
            'purpose'      => fake()->randomElement([
                'Capstone Project Defense Preparation',
                'Computer Engineering Embedded Systems Lab Experiment',
                'Campus Documentary Video Production and Editing',
                'Interactive Multimedia Presentation and Showcase',
                'Research Data Gathering in Remote Fieldwork',
            ]),
            'status'            => ReservationStatus::Pending,
            'rejection_remarks' => null,
            'approved_by'       => null,
            'approved_at'       => null,
        ];
    }

    /**
     * Set reservation status to pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => ReservationStatus::Pending,
            'rejection_remarks' => null,
            'approved_by'       => null,
            'approved_at'       => null,
        ]);
    }

    /**
     * Set reservation status to approved.
     */
    public function approved(?User $approver = null, ?ItemUnit $unit = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => ReservationStatus::Approved,
            'approved_by'  => $approver?->id ?? User::factory(),
            'approved_at'  => now(),
            'item_unit_id' => $unit?->id ?? ($attributes['item_unit_id'] ?? null),
        ]);
    }

    /**
     * Set reservation status to rejected with remarks.
     */
    public function rejected(string $remarks = 'Equipment schedule conflicts with university examination period.', ?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => ReservationStatus::Rejected,
            'approved_by'       => $approver?->id ?? User::factory(),
            'approved_at'       => now(),
            'rejection_remarks' => $remarks,
        ]);
    }

    /**
     * Set reservation status to checked out.
     */
    public function checkedOut(?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => ReservationStatus::CheckedOut,
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now()->subDay(),
        ]);
    }

    /**
     * Set reservation status to expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'     => ReservationStatus::Expired,
            'start_time' => now()->subDays(4),
            'end_time'   => now()->subDays(2),
        ]);
    }

    /**
     * Set reservation status to cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled,
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
     * Scope to a specific borrower.
     */
    public function forUser(User|int $user): static
    {
        $userId = is_int($user) ? $user : $user->id;

        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
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
