<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Payment;
use App\Models\Penalty;
use App\Enums\PaymentStatus;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id'                => Organization::factory(),
            'penalty_id'                     => Penalty::factory(),
            'user_id'                        => User::factory(),
            'payment_method_id'              => PaymentMethod::factory(),
            'organization_payment_method_id' => null,
            'amount_paid'                    => 500.00,
            'reference_number'               => null,
            'payment_proof_url'              => null,
            'paid_at'                        => now(),
            'status'                         => PaymentStatus::Verified,
            'received_by'                    => User::factory(),
            'notes'                          => 'Collected at circulation desk.',
        ];
    }

    /**
     * Cash payment channel.
     */
    public function cash(): static
    {
        return $this->state(function (): array {
            $cash = PaymentMethod::firstOrCreate(
                ['payment_code' => 'cash'],
                ['name' => 'Cash', 'description' => 'Over-the-counter Cash Payment', 'is_active' => true]
            );

            return [
                'payment_method_id'              => $cash->id,
                'organization_payment_method_id' => null,
                'reference_number'               => null,
                'notes'                          => 'Cash payment collected at circulation desk.',
            ];
        });
    }

    /**
     * GCash digital payment channel.
     */
    public function gcash(): static
    {
        return $this->state(function (): array {
            $gcash = PaymentMethod::firstOrCreate(
                ['payment_code' => 'gcash'],
                ['name' => 'GCash', 'description' => 'GCash Mobile Wallet', 'is_active' => true]
            );

            return [
                'payment_method_id' => $gcash->id,
                'reference_number'  => 'GC-'.$this->faker->numerify('############'),
                'notes'             => 'GCash digital transfer verified.',
            ];
        });
    }

    /**
     * Maya digital payment channel.
     */
    public function maya(): static
    {
        return $this->state(function (): array {
            $maya = PaymentMethod::firstOrCreate(
                ['payment_code' => 'maya'],
                ['name' => 'Maya', 'description' => 'Maya Digital Wallet & Bank', 'is_active' => true]
            );

            return [
                'payment_method_id' => $maya->id,
                'reference_number'  => 'MY-'.$this->faker->numerify('############'),
                'notes'             => 'Maya digital transfer verified.',
            ];
        });
    }

    /**
     * Verified payment status.
     */
    public function verified(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Verified,
        ]);
    }

    /**
     * Pending verification payment status.
     */
    public function pendingVerification(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::PendingVerification,
        ]);
    }
}
