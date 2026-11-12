<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailableAppsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_apps(): void
    {
        $this->getJson('/api/apps')->assertUnauthorized();
    }

    public function test_a_profile_must_be_chosen_first(): void
    {
        $this->withHeader('Referer', 'http://localhost')
            ->actingAs(User::factory()->create())
            ->getJson('/api/apps')
            ->assertConflict()
            ->assertJsonPath('code', 'profile_required');
    }

    public function test_signed_in_users_get_the_app_catalog_in_order(): void
    {
        config(['apps' => [
            'gifts' => ['name' => 'Gift Lists', 'description' => 'Wish lists.'],
            'groceries' => ['name' => 'Groceries', 'description' => 'Shared shopping list.'],
        ]]);

        $this->actingAsProfile(Profile::factory()->admin()->create())
            ->getJson('/api/apps')
            ->assertOk()
            ->assertExactJson(['data' => [
                ['key' => 'gifts', 'name' => 'Gift Lists', 'description' => 'Wish lists.'],
                ['key' => 'groceries', 'name' => 'Groceries', 'description' => 'Shared shopping list.'],
            ]]);
    }
}
