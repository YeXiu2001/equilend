<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use App\Models\ItemUnit;
use App\Enums\LoanStatus;
use App\Models\Reservation;
use App\Models\Organization;
use App\Enums\ItemUnitStatus;
use App\Enums\ItemUnitCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_unit_id' => ItemUnit::factory()->available(),
            'item_id'      => function (array $attributes) {
                if (isset($attributes['item_unit_id'])) {
                    $unit = ItemUnit::find($attributes['item_unit_id']);

                    if ($unit !== null) {
                        return $unit->item_id;
                    }
                }

                return Item::factory();
            },
            'organization_id' => function (array $attributes) {
                if (isset($attributes['item_unit_id'])) {
                    $unit = ItemUnit::find($attributes['item_unit_id']);

                    if ($unit !== null) {
                        return $unit->organization_id;
                    }
                }

                if (isset($attributes['item_id'])) {
                    $item = Item::find($attributes['item_id']);

                    if ($item !== null) {
                        return $item->organization_id;
                    }
                }

                return Organization::factory();
            },
            'reservation_id'     => null,
            'user_id'            => User::factory(),
            'issued_by'          => User::factory(),
            'received_by'        => null,
            'borrowed_at'        => now(),
            'due_at'             => now()->addDays(2),
            'returned_at'        => null,
            'checkout_condition' => ItemUnitCondition::Good,
            'checkin_condition'  => null,
            'status'             => LoanStatus::Active,
            'notes'              => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Loan $loan): void {
            if ($loan->status === LoanStatus::Active || $loan->status === LoanStatus::Overdue) {
                $loan->itemUnit?->update(['status' => ItemUnitStatus::Borrowed]);
            } elseif ($loan->status === LoanStatus::Returned) {
                $loan->itemUnit?->update([
                    'status'    => ItemUnitStatus::Available,
                    'condition' => $loan->checkin_condition ?? ItemUnitCondition::Good,
                ]);
            } elseif ($loan->status === LoanStatus::Damaged) {
                $loan->itemUnit?->update([
                    'status'    => ItemUnitStatus::Damaged,
                    'condition' => ItemUnitCondition::Poor,
                ]);
            } elseif ($loan->status === LoanStatus::Lost) {
                $loan->itemUnit?->update([
                    'status' => ItemUnitStatus::Lost,
                ]);
            }
        });
    }

    /**
     * Active loan currently in progress.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => LoanStatus::Active,
            'borrowed_at'       => now()->subHours(4),
            'due_at'            => now()->addDays(1),
            'returned_at'       => null,
            'received_by'       => null,
            'checkin_condition' => null,
        ]);
    }

    /**
     * Completed loan returned on time in good condition.
     */
    public function returned(?User $receiver = null, ItemUnitCondition $condition = ItemUnitCondition::Good): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => LoanStatus::Returned,
            'borrowed_at'       => now()->subDays(3),
            'due_at'            => now()->subDays(1),
            'returned_at'       => now()->subHours(2),
            'received_by'       => $receiver?->id ?? User::factory(),
            'checkin_condition' => $condition,
        ]);
    }

    /**
     * Overdue loan where due_at is in the past and returned_at is null.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => LoanStatus::Overdue,
            'borrowed_at'       => now()->subDays(5),
            'due_at'            => now()->subDays(2),
            'returned_at'       => null,
            'received_by'       => null,
            'checkin_condition' => null,
        ]);
    }

    /**
     * Returned loan marked damaged upon return.
     */
    public function damaged(?User $receiver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => LoanStatus::Damaged,
            'borrowed_at'       => now()->subDays(2),
            'due_at'            => now()->addHours(6),
            'returned_at'       => now(),
            'received_by'       => $receiver?->id ?? User::factory(),
            'checkin_condition' => ItemUnitCondition::Poor,
            'notes'             => 'Physical damage identified during circulation desk return check.',
        ]);
    }

    /**
     * Loan marked as lost.
     */
    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => LoanStatus::Lost,
            'borrowed_at' => now()->subDays(7),
            'due_at'      => now()->subDays(4),
            'returned_at' => null,
            'received_by' => null,
            'notes'       => 'Borrower reported physical unit lost during campus transport.',
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
     * Scope to a specific item.
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
            'item_unit_id'       => $unitModel->id,
            'item_id'            => $unitModel->item_id,
            'organization_id'    => $unitModel->organization_id,
            'checkout_condition' => $unitModel->condition,
        ]);
    }

    /**
     * Associate with an approved reservation.
     */
    public function forReservation(Reservation|int $reservation): static
    {
        $resModel = $reservation instanceof Reservation ? $reservation : Reservation::findOrFail($reservation);

        return $this->state(fn (array $attributes) => [
            'reservation_id'  => $resModel->id,
            'organization_id' => $resModel->organization_id,
            'user_id'         => $resModel->user_id,
            'item_id'         => $resModel->item_id,
        ]);
    }
}
