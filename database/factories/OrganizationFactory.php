<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'        => fake()->company(),
            'code'        => mb_strtoupper(fake()->unique()->lexify('???')),
            'email'       => fake()->unique()->companyEmail(),
            'phone'       => fake()->phoneNumber(),
            'address'     => fake()->address(),
            'description' => fake()->sentence(),
            'is_active'   => true,
        ];
    }

    /**
     * Indicate that the organization is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
