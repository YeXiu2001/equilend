<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Item;
use App\Models\Category;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
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
            'category_id'     => Category::factory(),
            'name'            => ucfirst(fake()->words(3, true)),
            'brand'           => fake()->company(),
            'model_number'    => fake()->bothify('MOD-####'),
            'description'     => fake()->sentence(12),
            'specifications'  => [
                'color'    => fake()->safeColorName(),
                'warranty' => '1 Year Official Warranty',
            ],
            'image_url' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Assign to a specific organization.
     */
    public function forOrganization(Organization|int $organization): static
    {
        $orgId = $organization instanceof Organization ? $organization->id : $organization;

        return $this->state(fn (array $attributes) => [
            'organization_id' => $orgId,
        ]);
    }

    /**
     * Assign to a specific category.
     */
    public function forCategory(Category|int $category): static
    {
        $categoryId = $category instanceof Category ? $category->id : $category;

        return $this->state(fn (array $attributes) => [
            'category_id' => $categoryId,
        ]);
    }
}
