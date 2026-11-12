<?php

namespace Tests\Feature;

use App\Groups\GroupInvites;
use App\Mail\LoginCodeMail;
use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase;

    private Group $family;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->withHeader('Referer', 'http://localhost');
        $this->family = Group::factory()->create(['name' => 'Robinson family']);
    }

    public function test_a_newcomer_signs_up_and_joins_through_the_link(): void
    {
        $token = $this->invite();

        $this->getJson("/api/invites/{$token}")
            ->assertOk()
            ->assertJsonPath('data.group.name', 'Robinson family')
            ->assertJsonPath('data.is_member', false);

        $this->postJson("/api/invites/{$token}/register", ['email' => ' Brother@Example.com '])->assertAccepted();
        $brother = User::query()->firstWhere('email', 'brother@example.com');
        $this->assertNotNull($brother);
        $this->assertCount(0, $brother->groups, 'Joining waits until they prove they own the email.');

        $code = Mail::queued(LoginCodeMail::class)->last()->code;
        $this->postJson('/api/auth/verify-code', ['email' => 'brother@example.com', 'code' => $code])->assertOk();

        $this->postJson("/api/invites/{$token}/accept")->assertOk()->assertJsonPath('data.name', 'Robinson family');
        $this->assertTrue($this->family->members()->whereKey($brother->id)->exists());
    }

    public function test_a_signed_in_account_joins_with_one_tap(): void
    {
        $token = $this->invite();
        $profile = Profile::factory()->admin()->create();

        $this->actingAsProfile($profile)->postJson("/api/invites/{$token}/accept")->assertOk();
        $this->getJson('/api/groups')->assertJsonPath('data.0.name', 'Robinson family');
    }

    public function test_joining_needs_a_signed_in_account(): void
    {
        $this->postJson("/api/invites/{$this->invite()}/accept")->assertUnauthorized();
    }

    public function test_a_single_use_link_works_once_but_rejoining_does_not_use_it_up(): void
    {
        $token = $this->invite(maxUses: 1);
        $first = User::factory()->create();

        $this->actingAs($first)->postJson("/api/invites/{$token}/accept")->assertOk();
        $this->actingAs($first)->postJson("/api/invites/{$token}/accept")->assertNotFound(); // used up
        $this->assertSame(1, $this->family->invites()->sole()->uses);

        $this->actingAs(User::factory()->create())->postJson("/api/invites/{$token}/accept")->assertNotFound();
        $this->postJson("/api/invites/{$token}/register", ['email' => 'late@example.com'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'late@example.com']);
    }

    public function test_a_multi_use_link_does_not_count_existing_members(): void
    {
        $token = $this->invite(maxUses: 2);
        $member = User::factory()->create();
        $this->family->addMember($member);

        $this->actingAs($member)->postJson("/api/invites/{$token}/accept")->assertOk();

        $this->assertSame(0, $this->family->invites()->sole()->uses);
    }

    public function test_expired_and_revoked_links_stop_working(): void
    {
        $expired = $this->invite(days: 1);
        $revoked = $this->invite();
        app(GroupInvites::class)->revoke($this->family->invites()->latest('id')->first());

        $this->travel(2)->days();

        $this->getJson("/api/invites/{$expired}")->assertNotFound();
        $this->getJson("/api/invites/{$revoked}")->assertNotFound();
        $this->getJson('/api/invites/'.str_repeat('a', 48))->assertNotFound();
    }

    public function test_links_are_stored_only_as_hashes(): void
    {
        $token = $this->invite();

        $this->assertDatabaseMissing('group_invites', ['token_hash' => $token]);
        $this->assertDatabaseMissing('group_invites', ['token_hash' => hash('sha256', $token)]);
    }

    private function invite(?int $maxUses = 1, int $days = 7): string
    {
        [, $url] = app(GroupInvites::class)->create($this->family, null, $maxUses, $days);

        return basename($url);
    }
}
