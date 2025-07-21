<?php

namespace Wink\ControllerGenerator\Tests\Fixtures\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Post;
use Wink\ControllerGenerator\Tests\Fixtures\Models\User;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Wink\ControllerGenerator\Tests\Fixtures\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6, true);
        
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => fake()->paragraphs(5, true),
            'excerpt' => fake()->paragraph(),
            'published_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'featured_image' => fake()->optional(0.3)->imageUrl(800, 600, 'articles'),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_featured' => fake()->boolean(20),
            'meta_data' => fake()->optional(0.5)->randomElements([
                'tags' => fake()->words(3),
                'reading_time' => fake()->numberBetween(2, 15),
                'difficulty' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            ]),
        ];
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'featured_image' => fake()->imageUrl(1200, 800, 'articles'),
        ]);
    }

    /**
     * Indicate that the post is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}