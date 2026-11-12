<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfileAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_profile_uploads_a_photo_that_is_cropped_and_re_encoded(): void
    {
        $profile = Profile::factory()->create();

        $response = $this->actingAsProfile($profile)
            ->post("/api/profiles/{$profile->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg', 800, 600)], ['Accept' => 'application/json'])
            ->assertOk();

        $url = $response->json('data.avatar_url');
        $this->assertStringStartsWith("/api/profiles/{$profile->id}/avatar?v=", $url);

        $image = $this->get($url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp')
            ->getContent();
        [$width, $height] = getimagesizefromstring($image);
        $this->assertSame([256, 256], [$width, $height]);

        // A new photo gets a new URL, so cached copies of the old one never show.
        $this->post("/api/profiles/{$profile->id}/avatar", ['image' => UploadedFile::fake()->image('new.png', 100, 100)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.avatar_url', fn ($next) => $next !== $url);
    }

    public function test_files_that_are_not_photos_are_rejected(): void
    {
        $profile = Profile::factory()->create();

        $this->actingAsProfile($profile)
            ->post("/api/profiles/{$profile->id}/avatar", ['image' => UploadedFile::fake()->create('evil.jpg', 10, 'image/jpeg')], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertNull($profile->fresh()->avatar_version);
    }

    public function test_removing_a_photo(): void
    {
        $profile = Profile::factory()->create();
        $this->actingAsProfile($profile)
            ->post("/api/profiles/{$profile->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json']);

        $this->deleteJson("/api/profiles/{$profile->id}/avatar")
            ->assertOk()
            ->assertJsonPath('data.avatar_url', null);

        $this->get("/api/profiles/{$profile->id}/avatar")->assertNotFound();
        $this->assertDatabaseCount('profile_avatars', 0);
    }

    public function test_members_change_only_their_own_photo_and_admins_change_any(): void
    {
        $admin = Profile::factory()->admin()->create();
        $member = Profile::factory()->for($admin->user)->create();
        $photo = fn () => ['image' => UploadedFile::fake()->image('me.jpg')];

        $this->actingAsProfile($member)
            ->post("/api/profiles/{$admin->id}/avatar", $photo(), ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->actingAsProfile($admin)
            ->post("/api/profiles/{$member->id}/avatar", $photo(), ['Accept' => 'application/json'])
            ->assertOk();
    }

    public function test_photos_are_visible_to_the_account_and_its_families_only(): void
    {
        $owner = Profile::factory()->create();
        $this->actingAsProfile($owner)
            ->post("/api/profiles/{$owner->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json']);
        $url = $owner->fresh()->avatarUrl();

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get($url)->assertNotFound();

        $group = Group::factory()->create();
        $group->addMember($owner->user);
        $group->addMember($stranger);
        $this->actingAs($stranger)->get($url)->assertOk();
    }

    public function test_deleting_a_profile_deletes_its_photo(): void
    {
        $admin = Profile::factory()->admin()->create();
        $other = Profile::factory()->for($admin->user)->create();
        $this->actingAsProfile($admin)
            ->post("/api/profiles/{$other->id}/avatar", ['image' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json']);

        $this->deleteJson("/api/profiles/{$other->id}")->assertNoContent();

        $this->assertDatabaseCount('profile_avatars', 0);
    }
}
