<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupInvite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Invites are normally made with App\Groups\GroupInvites::create(), which
 * returns the token; factory invites have an unknown token.
 *
 * @extends Factory<GroupInvite>
 */
class GroupInviteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'max_uses' => 1,
            'expires_at' => now()->addWeek(),
        ];
    }
}
