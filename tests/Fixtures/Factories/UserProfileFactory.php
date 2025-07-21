<?php

namespace Wink\ControllerGenerator\Tests\Fixtures\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Wink\ControllerGenerator\Tests\Fixtures\Models\UserProfile;
use Wink\ControllerGenerator\Tests\Fixtures\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Wink\ControllerGenerator\Tests\Fixtures\Models\UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'date_of_birth' => fake()->optional(0.8)->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->optional(0.6)->randomElement(['male', 'female', 'other', 'prefer_not_to_say']),
            'address' => fake()->optional(0.5)->streetAddress(),
            'city' => fake()->optional(0.6)->city(),
            'state' => fake()->optional(0.6)->state(),
            'postal_code' => fake()->optional(0.6)->postcode(),
            'country' => fake()->optional(0.7)->country(),
            'bio' => fake()->optional(0.4)->paragraph(),
            'avatar' => fake()->optional(0.3)->imageUrl(200, 200, 'people'),
            'timezone' => fake()->timezone(),
            'locale' => fake()->randomElement(['en', 'es', 'fr', 'de', 'it', 'pt', 'ja', 'ko', 'zh']),
            'preferences' => fake()->optional(0.5)->randomElements([
                'newsletter' => fake()->boolean(),
                'notifications' => fake()->boolean(),
                'theme' => fake()->randomElement(['light', 'dark', 'auto']),
                'language' => fake()->randomElement(['en', 'es', 'fr']),
            ]),
        ];
    }

    /**
     * Indicate that the profile belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the profile has complete information.
     */
    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'bio' => fake()->paragraph(),
            'avatar' => fake()->imageUrl(200, 200, 'people'),
        ]);
    }

    /**
     * Indicate that the profile has minimal information.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => null,
            'date_of_birth' => null,
            'gender' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'bio' => null,
            'avatar' => null,
        ]);
    }

    /**
     * Indicate that the profile is for a young user.
     */
    public function young(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the profile is for a senior user.
     */
    public function senior(): static
    {
        return $this->state(fn (array $attributes) => [
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-60 years')->format('Y-m-d'),
        ]);
    }

    /**
     * Indicate that the profile has international settings.
     */
    public function international(): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => fake()->randomElement(['Canada', 'United Kingdom', 'Australia', 'Germany', 'France', 'Japan']),
            'timezone' => fake()->randomElement(['America/Toronto', 'Europe/London', 'Australia/Sydney', 'Europe/Berlin', 'Europe/Paris', 'Asia/Tokyo']),
            'locale' => fake()->randomElement(['en_CA', 'en_GB', 'en_AU', 'de_DE', 'fr_FR', 'ja_JP']),
        ]);
    }
}