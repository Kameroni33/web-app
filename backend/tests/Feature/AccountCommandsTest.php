<?php

namespace Tests\Feature;

use App\Enums\GroupRole;
use App\Enums\ProfileRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_account_with_a_normalised_email_and_no_family(): void
    {
        $this->artisan('accounts:create', ['email' => '  Mom@Example.COM '])->assertSuccessful();

        $user = User::query()->sole();
        $this->assertSame('mom@example.com', $user->email);
        $this->assertCount(0, $user->groups);
        $this->assertCount(0, $user->profiles);
    }

    public function test_the_old_command_name_still_works_and_is_idempotent(): void
    {
        User::factory()->create(['email' => 'mom@example.com']);

        $this->artisan('app:create-login', ['email' => 'mom@example.com'])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_rejects_an_invalid_email_or_unknown_group(): void
    {
        $this->artisan('accounts:create', ['email' => 'not-an-email'])->assertFailed();
        $this->artisan('accounts:create', ['email' => 'mom@example.com', '--group' => [999]])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_can_name_the_admin_profile_and_join_groups(): void
    {
        $family = Group::factory()->create();

        $this->artisan('accounts:create', ['email' => 'mom@example.com', '--name' => 'Mom', '--group' => [$family->id]])
            ->assertSuccessful();

        $user = User::query()->sole();
        $this->assertSame(ProfileRole::Admin, $user->profiles->sole()->role);
        $this->assertTrue($family->members->contains($user));
    }

    public function test_group_commands_manage_membership(): void
    {
        $user = User::factory()->create(['email' => 'mom@example.com']);

        $this->artisan('groups:create', ['name' => 'Robinson family'])->assertSuccessful();
        $group = Group::query()->sole();

        $this->artisan('groups:add-member', ['group' => $group->id, 'email' => 'nobody@example.com'])->assertFailed();
        $this->artisan('groups:add-member', ['group' => $group->id, 'email' => 'Mom@example.com', '--role' => 'boss'])->assertFailed();
        $this->artisan('groups:add-member', ['group' => $group->id, 'email' => 'Mom@example.com', '--role' => 'manager'])->assertSuccessful();
        $this->assertSame(GroupRole::Manager, $group->members()->sole()->membership->role);

        $this->artisan('groups:list')->assertSuccessful();
        $this->artisan('accounts:list')->assertSuccessful();

        $this->artisan('groups:remove-member', ['group' => $group->id, 'email' => 'mom@example.com'])->assertSuccessful();
        $this->artisan('groups:remove-member', ['group' => $group->id, 'email' => 'mom@example.com'])->assertFailed();
        $this->assertCount(0, $user->groups()->get());
    }
}
