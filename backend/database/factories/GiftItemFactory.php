<?php

namespace Database\Factories;

use App\Models\GiftItem;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftItem>
 */
class GiftItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'title' => fake()->words(3, true),
            'links' => [],
            'position' => 0,
        ];
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => ['received_at' => now()]);
    }
}
