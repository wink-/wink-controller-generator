<?php

namespace Wink\ControllerGenerator\Tests\Fixtures\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Product;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Wink\ControllerGenerator\Tests\Fixtures\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        $price = fake()->randomFloat(2, 10, 1000);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->paragraph(),
            'price' => $price,
            'cost' => $price * fake()->randomFloat(2, 0.3, 0.8), // Cost is typically 30-80% of price
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'weight' => fake()->randomFloat(3, 0.1, 50.0),
            'dimensions' => [
                'length' => fake()->randomFloat(2, 5, 100),
                'width' => fake()->randomFloat(2, 5, 100),
                'height' => fake()->randomFloat(2, 1, 50),
                'unit' => 'cm',
            ],
            'category_id' => Category::factory(),
            'brand_id' => fake()->optional(0.8)->numberBetween(1, 50),
            'is_active' => fake()->boolean(85),
            'is_featured' => fake()->boolean(15),
            'meta_title' => fake()->optional(0.6)->sentence(8),
            'meta_description' => fake()->optional(0.6)->paragraph(),
            'attributes' => fake()->optional(0.7)->randomElements([
                'color' => fake()->colorName(),
                'size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL', 'XXL']),
                'material' => fake()->randomElement(['Cotton', 'Polyester', 'Wool', 'Silk', 'Leather']),
                'origin' => fake()->country(),
            ]),
        ];
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product is in stock.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => fake()->numberBetween(1, 100),
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is expensive.
     */
    public function expensive(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 500, 5000),
        ]);
    }

    /**
     * Indicate that the product is cheap.
     */
    public function cheap(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 1, 50),
        ]);
    }
}