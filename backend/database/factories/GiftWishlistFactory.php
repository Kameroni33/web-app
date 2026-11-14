<?php

namespace Database\Factories;

use App\Models\GiftWishlist;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiftWishlist>
 */
class GiftWishlistFactory extends Factory
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
            'label' => 'Amazon',
            'url' => 'https://www.amazon.com/hz/wishlist/ls/'.fake()->bothify('??##??##'),
        ];
    }
}
