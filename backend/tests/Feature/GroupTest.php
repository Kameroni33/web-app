<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_see_their_families_with_every_members_profiles(): void
    {
        $kam = Profile::factory()->admin()->create(['name' => 'Kam']);
        $mom = Profile::factory()->admin()->create(['name' => 'Mom']);
        Profile::factory()->for($mom->user)->create(['name' => 'Dad']);
        $outsider = Profile::factory()->admin()->create(['name' => 'Neighbour']);

        $family = Group::factory()->create(['name' => 'Robinson family']);
        $family->addMember($kam->user);
        $family->addMember($mom->user);
        Group::factory()->create(['name' => 'Other family'])->addMember($outsider->user);

        $this->actingAsProfile($kam)
            ->getJson('/api/groups')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Robinson family')
            ->assertJsonPath('data.0.members.*.name', ['Dad', 'Kam', 'Mom']);
    }

    public function test_an_account_can_be_in_no_family(): void
    {
        $this->actingAsProfile(Profile::factory()->admin()->create())
            ->getJson('/api/groups')
            ->assertOk()
            ->assertExactJson(['data' => []]);
    }
}
