<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'            => ucfirst($name),
            'slug'            => fn (array $attributes) => Str::slug($attributes['name']),
            'organization_id' => null,
            'description'     => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the category is scoped to an organization.
     */
    public function forOrganization(Organization|int|null $organization = null): static
    {
        return $this->state(function () use ($organization) {
            $orgId = $organization instanceof Organization
                ? $organization->id
                : ($organization ?? Organization::factory()->create()->id);

            return [
                'organization_id' => $orgId,
            ];
        });
    }
}
