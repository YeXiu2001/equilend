<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Loan;
use App\Models\User;
use App\Models\Penalty;
use App\Models\ItemUnit;
use App\Enums\PenaltyType;
use App\Enums\PenaltyStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penalty>
 */
class PenaltyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomElement([150.00, 300.00, 500.00, 1000.00, 1500.00]);

        return [
            'organization_id'   => Organization::factory(),
            'loan_id'           => null,
            'user_id'           => User::factory(),
            'item_unit_id'      => null,
            'type'              => PenaltyType::Overdue,
            'amount'            => $amount,
            'paid_amount'       => 0.00,
            'balance_remaining' => $amount,
            'status'            => PenaltyStatus::Unpaid,
            'assessment_notes'  => $this->faker->sentence(),
            'assessed_by'       => User::factory(),
            'waived_reason'     => null,
        ];
    }

    /**
     * Penalty is unpaid.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'paid_amount'       => 0.00,
            'balance_remaining' => $attributes['amount'] ?? 500.00,
            'status'            => PenaltyStatus::Unpaid,
            'waived_reason'     => null,
        ]);
    }

    /**
     * Penalty is partially paid.
     */
    public function partiallyPaid(?float $paidAmount = null): static
    {
        return $this->state(function (array $attributes) use ($paidAmount): array {
            $amount = (float) ($attributes['amount'] ?? 1000.00);
            $paid   = $paidAmount ?? round($amount * 0.4, 2);

            return [
                'amount'            => $amount,
                'paid_amount'       => $paid,
                'balance_remaining' => max(0.0, round($amount - $paid, 2)),
                'status'            => PenaltyStatus::PartiallyPaid,
            ];
        });
    }

    /**
     * Penalty is fully paid.
     */
    public function paid(): static
    {
        return $this->state(function (array $attributes): array {
            $amount = (float) ($attributes['amount'] ?? 500.00);

            return [
                'amount'            => $amount,
                'paid_amount'       => $amount,
                'balance_remaining' => 0.00,
                'status'            => PenaltyStatus::Paid,
            ];
        });
    }

    /**
     * Penalty is waived with a reason.
     */
    public function waived(string $reason = 'Authorized department lab waiver'): static
    {
        return $this->state(fn (array $attributes): array => [
            'balance_remaining' => 0.00,
            'status'            => PenaltyStatus::Waived,
            'waived_reason'     => $reason,
        ]);
    }

    /**
     * Penalty is voided.
     */
    public function voided(string $reason = 'Assessed in error'): static
    {
        return $this->state(fn (array $attributes): array => [
            'balance_remaining' => 0.00,
            'status'            => PenaltyStatus::Voided,
            'waived_reason'     => $reason,
        ]);
    }

    /**
     * Overdue penalty type.
     */
    public function overdueType(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type'             => PenaltyType::Overdue,
            'assessment_notes' => 'Overdue equipment return penalty.',
        ]);
    }

    /**
     * Damage penalty type.
     */
    public function damageType(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type'             => PenaltyType::Damage,
            'assessment_notes' => 'Physical damage identified during check-in inspection.',
        ]);
    }

    /**
     * Loss penalty type.
     */
    public function lossType(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type'             => PenaltyType::Loss,
            'assessment_notes' => 'Equipment unit reported lost by borrower.',
        ]);
    }

    /**
     * Link penalty to a specific loan and its organization/borrower/unit.
     */
    public function forLoan(Loan $loan): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $loan->organization_id,
            'loan_id'         => $loan->id,
            'user_id'         => $loan->user_id,
            'item_unit_id'    => $loan->item_unit_id,
        ]);
    }

    /**
     * Link penalty to a specific item unit.
     */
    public function forUnit(ItemUnit $unit): static
    {
        return $this->state(fn (array $attributes): array => [
            'organization_id' => $unit->organization_id,
            'item_unit_id'    => $unit->id,
        ]);
    }
}
