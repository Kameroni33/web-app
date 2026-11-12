<?php

namespace Tests\Feature;

use App\Auth\ActiveProfile;
use App\Enums\ProfileRole;
use App\Mail\LoginCodeMail;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_time_setup_creates_the_admin_profile_and_uses_it(): void
    {
        $user = User::factory()->create();
        $this->withHeader('Referer', 'http://localhost')->actingAs($user);

        $this->getJson('/api/me')->assertOk()->assertJsonMissingPath('data.profile');

        $this->postJson('/api/profiles', ['name' => '  Kam  ', 'role' => 'member'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Kam')
            ->assertJsonPath('data.role', 'admin');

        $this->getJson('/api/me')->assertJsonPath('data.profile.name', 'Kam');
        $this->getJson('/api/apps')->assertOk();
    }

    public function test_after_setup_adding_profiles_needs_an_active_admin(): void
    {
        $admin = Profile::factory()->admin()->create();
        $user = $admin->user;

        // Profiles exist but none chosen on this device.
        $this->withHeader('Referer', 'http://localhost')->actingAs($user)
            ->postJson('/api/profiles', ['name' => 'Sneaky'])
            ->assertForbidden();

        $this->actingAsProfile($admin)
            ->postJson('/api/profiles', ['name' => 'Dad'])
            ->assertCreated()
            ->assertJsonPath('data.role', 'member');

        $this->actingAsProfile(Profile::factory()->for($user)->create())
            ->postJson('/api/profiles', ['name' => 'Another'])
            ->assertForbidden();

        $this->assertDatabaseHas('profiles', ['user_id' => $user->id, 'name' => 'Dad']);
        $this->assertDatabaseMissing('profiles', ['name' => 'Sneaky']);
        $this->assertDatabaseMissing('profiles', ['name' => 'Another']);
    }

    public function test_choosing_a_profile_is_per_session_and_limited_to_the_account(): void
    {
        $mom = Profile::factory()->admin()->create(['name' => 'Mom']);
        $kid = Profile::factory()->for($mom->user)->create(['name' => 'Kid']);
        $stranger = Profile::factory()->admin()->create();

        $this->withHeader('Referer', 'http://localhost')->actingAs($mom->user);

        $this->getJson('/api/profiles')->assertOk()->assertJsonCount(2, 'data');
        $this->postJson("/api/profiles/{$stranger->id}/select")->assertNotFound();
        $this->postJson("/api/profiles/{$kid->id}/select")->assertOk();
        $this->getJson('/api/me')->assertJsonPath('data.profile.name', 'Kid');
    }

    public function test_members_can_rename_only_themselves(): void
    {
        $admin = Profile::factory()->admin()->create();
        $member = Profile::factory()->for($admin->user)->create();

        $this->actingAsProfile($member);
        $this->patchJson("/api/profiles/{$member->id}", ['name' => 'Little Bro'])->assertOk();
        $this->patchJson("/api/profiles/{$admin->id}", ['name' => 'Hacked'])->assertForbidden();
        $this->patchJson("/api/profiles/{$member->id}", ['role' => 'admin'])->assertForbidden();

        $this->assertSame('Little Bro', $member->fresh()->name);
        $this->assertSame(ProfileRole::Member, $member->fresh()->role);
    }

    public function test_admins_manage_roles_but_an_account_keeps_one_admin(): void
    {
        $admin = Profile::factory()->admin()->create();
        $member = Profile::factory()->for($admin->user)->create();

        $this->actingAsProfile($admin);
        $this->patchJson("/api/profiles/{$admin->id}", ['role' => 'member'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->patchJson("/api/profiles/{$member->id}", ['role' => 'admin'])->assertOk();
        $this->patchJson("/api/profiles/{$admin->id}", ['role' => 'member'])->assertOk();
    }

    public function test_admins_delete_other_profiles_but_not_their_own(): void
    {
        $admin = Profile::factory()->admin()->create();
        $member = Profile::factory()->for($admin->user)->create();

        $this->actingAsProfile($member)->deleteJson("/api/profiles/{$admin->id}")->assertForbidden();

        $this->actingAsProfile($admin);
        $this->deleteJson("/api/profiles/{$admin->id}")->assertForbidden();
        $this->deleteJson("/api/profiles/{$member->id}")->assertNoContent();
        $this->assertModelMissing($member);
    }

    public function test_another_accounts_profile_looks_missing(): void
    {
        $admin = Profile::factory()->admin()->create();
        $stranger = Profile::factory()->admin()->create();

        $this->actingAsProfile($admin);
        $this->patchJson("/api/profiles/{$stranger->id}", ['name' => 'Mine now'])->assertNotFound();
        $this->deleteJson("/api/profiles/{$stranger->id}")->assertNotFound();
    }

    public function test_a_deleted_profile_stops_working_on_other_devices(): void
    {
        $admin = Profile::factory()->admin()->create();
        $member = Profile::factory()->for($admin->user)->create();
        $member->delete();

        $this->actingAsProfile($member)->getJson('/api/apps')->assertConflict();
    }

    public function test_sign_in_picks_the_only_profile_automatically(): void
    {
        $profile = Profile::factory()->admin()->create();

        $this->signIn($profile->user)->assertJsonPath('data.profile.id', $profile->id);
        $this->assertSame($profile->id, session(ActiveProfile::SESSION_KEY));
    }

    public function test_sign_in_with_several_profiles_asks_who_is_using_it(): void
    {
        $profile = Profile::factory()->admin()->create();
        Profile::factory()->for($profile->user)->create();

        $this->signIn($profile->user)->assertJsonMissingPath('data.profile');
        $this->assertNull(session(ActiveProfile::SESSION_KEY));
    }

    private function signIn(User $user): TestResponse
    {
        Mail::fake();
        $this->withHeader('Referer', 'http://localhost')
            ->postJson('/api/auth/login-code', ['email' => $user->email])
            ->assertAccepted();
        $code = Mail::queued(LoginCodeMail::class)->last()->code;

        return $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $code])->assertOk();
    }
}
