<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_admin_accounts_admin_profile_can_manage_groups(): void
    {
        $regular = Profile::factory()->admin()->create();
        $this->actingAsProfile($regular)->getJson('/api/admin/groups')->assertForbidden();
        $this->getJson('/api/me')->assertJsonPath('data.can.manage_groups', false);

        $admin = Profile::factory()->admin()->for(User::factory()->state(['is_admin' => true]))->create();
        $kid = Profile::factory()->for($admin->user)->create();
        $this->actingAsProfile($kid)->getJson('/api/admin/groups')->assertForbidden();

        $this->actingAsProfile($admin)->getJson('/api/admin/groups')->assertOk();
        $this->getJson('/api/me')
            ->assertJsonPath('data.is_admin', true)
            ->assertJsonPath('data.can.manage_groups', true);
    }

    public function test_admins_create_rename_and_delete_groups(): void
    {
        $this->actingAsProfile($this->adminProfile());

        $id = $this->postJson('/api/admin/groups', ['name' => 'Robinson family'])
            ->assertCreated()
            ->assertJsonPath('data.member_count', 0)
            ->json('data.id');

        $this->patchJson("/api/admin/groups/{$id}", ['name' => 'Robinsons'])->assertOk()->assertJsonPath('data.name', 'Robinsons');
        $this->getJson('/api/admin/groups')->assertJsonPath('data.0.name', 'Robinsons');

        $this->deleteJson("/api/admin/groups/{$id}")->assertNoContent();
        $this->assertDatabaseCount('groups', 0);
    }

    public function test_admins_see_members_and_remove_them(): void
    {
        $group = Group::factory()->create();
        $member = Profile::factory()->admin()->create(['name' => 'Bro']);
        $group->addMember($member->user);

        $this->actingAsProfile($this->adminProfile());
        $this->getJson("/api/admin/groups/{$group->id}")
            ->assertOk()
            ->assertJsonPath('data.members.0.email', $member->user->email)
            ->assertJsonPath('data.members.0.profiles', ['Bro']);

        $this->deleteJson("/api/admin/groups/{$group->id}/members/{$member->user->id}")->assertNoContent();
        $this->deleteJson("/api/admin/groups/{$group->id}/members/{$member->user->id}")->assertNotFound();
        $this->assertModelExists($member->user);
    }

    public function test_admins_create_invite_links_and_revoke_them(): void
    {
        $group = Group::factory()->create();
        $this->actingAsProfile($this->adminProfile());

        $response = $this->postJson("/api/admin/groups/{$group->id}/invites", ['max_uses' => null, 'expires_in_days' => 30])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.max_uses', null);
        $this->assertMatchesRegularExpression('#/invite/[0-9a-f]{48}$#', $response->json('url'));

        $this->getJson("/api/admin/groups/{$group->id}")->assertJsonCount(1, 'data.invites')->assertJsonMissingPath('data.invites.0.url');

        $this->deleteJson('/api/admin/invites/'.$response->json('data.id'))->assertNoContent();
        $this->getJson("/api/admin/groups/{$group->id}")->assertJsonCount(0, 'data.invites');
        $this->getJson('/api/invites/'.basename($response->json('url')))->assertNotFound();
    }

    private function adminProfile(): Profile
    {
        return Profile::factory()->admin()->for(User::factory()->state(['is_admin' => true]))->create();
    }
}
