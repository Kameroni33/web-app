<?php

namespace Tests\Feature\Gifts;

use App\Models\GiftItem;
use App\Models\Group;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Profile, 1: Profile} me and a family member on another account
     */
    private function family(): array
    {
        $me = Profile::factory()->create(['name' => 'Kam']);
        $them = Profile::factory()->create(['name' => 'Mom']);
        $group = Group::factory()->create();
        $group->addMember($me->user);
        $group->addMember($them->user);

        return [$me, $them];
    }

    public function test_people_lists_your_account_and_your_families(): void
    {
        [$me, $mom] = $this->family();
        $partner = Profile::factory()->for($me->user)->create(['name' => 'Partner']);
        Profile::factory()->create(); // a stranger

        $this->actingAsProfile($me)->getJson('/api/gifts/people')
            ->assertOk()
            ->assertJsonPath('data.account.*.name', ['Partner'])
            ->assertJsonPath('data.families.0.people.*.name', ['Mom']);
    }

    public function test_claims_are_anonymous_and_hidden_from_the_owner(): void
    {
        [$me, $mom] = $this->family();
        $dad = Profile::factory()->for($mom->user)->create();
        $item = GiftItem::factory()->for($mom)->create();

        $this->actingAsProfile($me)->postJson("/api/gifts/items/{$item->id}/claim")
            ->assertOk()
            ->assertJsonPath('data.claim', 'mine');

        // Someone else sees that it's taken, not by whom.
        $this->actingAsProfile($dad)->getJson("/api/gifts/lists/{$mom->id}")
            ->assertJsonPath('data.items.0.claim', 'taken')
            ->assertJsonPath('data.profile.same_account', true)
            ->assertJsonMissingPath('data.items.0.claimed_by');

        // The owner's own list says nothing about claims.
        $this->actingAsProfile($mom)->getJson('/api/gifts/items')
            ->assertJsonMissingPath('data.0.claim');
        $this->actingAsProfile($mom)->getJson("/api/gifts/lists/{$mom->id}")->assertNotFound();
        $this->actingAsProfile($mom)->postJson("/api/gifts/items/{$item->id}/claim")->assertNotFound();
    }

    public function test_one_claim_per_item_and_only_the_claimer_can_undo_it(): void
    {
        [$me, $mom] = $this->family();
        $dad = Profile::factory()->for($mom->user)->create();
        $item = GiftItem::factory()->for($mom)->create();

        $this->actingAsProfile($me)->postJson("/api/gifts/items/{$item->id}/claim");
        $this->actingAsProfile($dad)->postJson("/api/gifts/items/{$item->id}/claim")->assertJsonPath('data.claim', 'taken');
        $this->actingAsProfile($dad)->deleteJson("/api/gifts/items/{$item->id}/claim")->assertJsonPath('data.claim', 'taken');

        $this->actingAsProfile($me)->deleteJson("/api/gifts/items/{$item->id}/claim")->assertJsonPath('data.claim', null);
    }

    public function test_strangers_cannot_see_or_claim(): void
    {
        $item = GiftItem::factory()->create();
        $stranger = Profile::factory()->create();

        $this->actingAsProfile($stranger)->getJson("/api/gifts/lists/{$item->profile_id}")->assertNotFound();
        $this->actingAsProfile($stranger)->postJson("/api/gifts/items/{$item->id}/claim")->assertNotFound();
    }

    public function test_received_items_are_hidden_and_deleted_ones_show_only_to_their_claimer(): void
    {
        [$me, $mom] = $this->family();
        $dad = Profile::factory()->for($mom->user)->create();
        GiftItem::factory()->for($mom)->received()->create(['title' => 'Got it']);
        $claimed = GiftItem::factory()->for($mom)->create(['title' => 'Claimed']);
        $gone = GiftItem::factory()->for($mom)->create(['title' => 'Gone']);

        $this->actingAsProfile($me)->postJson("/api/gifts/items/{$claimed->id}/claim");
        $claimed->delete();
        $gone->delete();

        $this->actingAsProfile($me)->getJson("/api/gifts/lists/{$mom->id}")
            ->assertJsonPath('data.items.*.title', ['Claimed'])
            ->assertJsonPath('data.items.0.removed', true);
        $this->actingAsProfile($dad)->getJson("/api/gifts/lists/{$mom->id}")
            ->assertJsonPath('data.items', []);

        // The claimer can still let go of a removed item.
        $this->actingAsProfile($me)->deleteJson("/api/gifts/items/{$claimed->id}/claim")->assertJsonPath('data.claim', null);
    }
}
