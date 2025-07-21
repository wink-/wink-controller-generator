<?php

namespace Wink\ControllerGenerator\Tests\Fixtures;

use Illuminate\Database\Seeder;
use Wink\ControllerGenerator\Tests\Fixtures\Models\User;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Category;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Post;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Product;
use Wink\ControllerGenerator\Tests\Fixtures\Models\UserProfile;

class TestSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            PostSeeder::class,
            ProductSeeder::class,
            UserProfileSeeder::class,
        ]);
    }
}

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->create();

        // Create specific test users
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@test.com',
            'role' => 'user',
        ]);

        User::factory()
            ->count(3)
            ->inactive()
            ->create();
    }
}

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create root categories
        $technology = Category::factory()->create([
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Technology related content',
        ]);

        $lifestyle = Category::factory()->create([
            'name' => 'Lifestyle',
            'slug' => 'lifestyle',
            'description' => 'Lifestyle and personal content',
        ]);

        $business = Category::factory()->create([
            'name' => 'Business',
            'slug' => 'business',
            'description' => 'Business and entrepreneurship',
        ]);

        // Create child categories
        Category::factory()->create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'parent_id' => $technology->id,
        ]);

        Category::factory()->create([
            'name' => 'Mobile Development',
            'slug' => 'mobile-development',
            'parent_id' => $technology->id,
        ]);

        Category::factory()->create([
            'name' => 'Health & Fitness',
            'slug' => 'health-fitness',
            'parent_id' => $lifestyle->id,
        ]);

        Category::factory()->create([
            'name' => 'Travel',
            'slug' => 'travel',
            'parent_id' => $lifestyle->id,
        ]);

        // Create some random categories
        Category::factory()
            ->count(5)
            ->create();
    }
}

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();

        // Create published posts
        Post::factory()
            ->count(20)
            ->published()
            ->create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Create draft posts
        Post::factory()
            ->count(10)
            ->draft()
            ->create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Create featured posts
        Post::factory()
            ->count(5)
            ->featured()
            ->published()
            ->create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
            ]);

        // Create archived posts
        Post::factory()
            ->count(3)
            ->archived()
            ->create([
                'user_id' => $users->random()->id,
                'category_id' => $categories->random()->id,
            ]);
    }
}

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all();

        // Create active products
        Product::factory()
            ->count(50)
            ->inStock()
            ->create([
                'category_id' => $categories->random()->id,
            ]);

        // Create featured products
        Product::factory()
            ->count(10)
            ->featured()
            ->inStock()
            ->create([
                'category_id' => $categories->random()->id,
            ]);

        // Create out of stock products
        Product::factory()
            ->count(5)
            ->outOfStock()
            ->create([
                'category_id' => $categories->random()->id,
            ]);

        // Create inactive products
        Product::factory()
            ->count(8)
            ->inactive()
            ->create([
                'category_id' => $categories->random()->id,
            ]);

        // Create expensive products
        Product::factory()
            ->count(3)
            ->expensive()
            ->featured()
            ->create([
                'category_id' => $categories->random()->id,
            ]);

        // Create cheap products
        Product::factory()
            ->count(12)
            ->cheap()
            ->create([
                'category_id' => $categories->random()->id,
            ]);
    }
}

class UserProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            UserProfile::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}

// Additional seeders for testing relationships and edge cases

class RelationshipTestSeeder extends Seeder
{
    /**
     * Run the database seeds for testing relationships.
     */
    public function run(): void
    {
        // Create a user with many posts
        $prolificUser = User::factory()->create([
            'name' => 'Prolific Writer',
            'email' => 'writer@test.com',
        ]);

        $techCategory = Category::where('slug', 'technology')->first();

        Post::factory()
            ->count(15)
            ->published()
            ->create([
                'user_id' => $prolificUser->id,
                'category_id' => $techCategory->id,
            ]);

        // Create a category with many products
        $electronicsCategory = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        Product::factory()
            ->count(25)
            ->create([
                'category_id' => $electronicsCategory->id,
            ]);
    }
}

class EdgeCaseSeeder extends Seeder
{
    /**
     * Run the database seeds for edge cases.
     */
    public function run(): void
    {
        // Create user without profile
        User::factory()->create([
            'name' => 'No Profile User',
            'email' => 'noprofile@test.com',
        ]);

        // Create category without posts or products
        Category::factory()->create([
            'name' => 'Empty Category',
            'slug' => 'empty-category',
        ]);

        // Create products with null category
        Product::factory()
            ->count(3)
            ->create([
                'category_id' => null,
            ]);

        // Create deeply nested categories
        $parent = Category::factory()->create([
            'name' => 'Level 1',
            'slug' => 'level-1',
        ]);

        $child = Category::factory()->create([
            'name' => 'Level 2',
            'slug' => 'level-2',
            'parent_id' => $parent->id,
        ]);

        Category::factory()->create([
            'name' => 'Level 3',
            'slug' => 'level-3',
            'parent_id' => $child->id,
        ]);
    }
}