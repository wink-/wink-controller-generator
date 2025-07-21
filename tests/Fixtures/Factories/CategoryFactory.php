<?php

namespace Wink\ControllerGenerator\Tests\Fixtures\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Wink\ControllerGenerator\Tests\Fixtures\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional(0.7)->paragraph(),
            'parent_id' => null,
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => fake()->boolean(90),
            'meta_title' => fake()->optional(0.5)->sentence(6),
            'meta_description' => fake()->optional(0.5)->paragraph(),
        ];
    }

    /**
     * Indicate that the category has a parent.
     */
    public function withParent(Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent ? $parent->id : Category::factory(),
        ]);
    }

    /**
     * Indicate that the category is a root category.
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a category tree with children.
     */
    public function withChildren(int $count = 3): static
    {
        return $this->afterCreating(function (Category $category) use ($count) {
            Category::factory()
                ->count($count)
                ->withParent($category)
                ->create();
        });
    }
}