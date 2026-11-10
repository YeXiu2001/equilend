<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => '$2y$04$pH3Ri/itPyiEbheC3kPj/eMf2AYCvroUo5ZZ1HmAksGA6W2HCw9B2', // Test123!
            'remember_token'    => Str::random(10),
            'pic_dir'           => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
