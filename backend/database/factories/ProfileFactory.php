<?php

namespace Database\Factories;

use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName(),
            'role' => ProfileRole::Member,
        ];
    }

    /**
     * The account's admin profile.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ProfileRole::Admin,
        ]);
    }
}
