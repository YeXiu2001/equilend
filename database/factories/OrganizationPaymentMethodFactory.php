<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PaymentMethod;
use App\Models\OrganizationPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationPaymentMethod>
 */
class OrganizationPaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id'   => Organization::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'account_name'      => fake()->company(),
            'account_numbers'   => [
                fake()->numerify('09#########'),
                fake()->numerify('09#########'),
            ],
            'is_enabled' => true,
        ];
    }

    /**
     * Indicate that the configuration is disabled.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
