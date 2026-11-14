<?php

namespace Tests\Feature\Gifts;

use App\Models\GiftItem;
use App\Models\GiftWishlist;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyListTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_editing_and_deleting_items(): void
    {
        $me = Profile::factory()->create();
        $this->actingAsProfile($me);

        $first = $this->postJson('/api/gifts/items', ['title' => 'Dune', 'category' => 'Books', 'links' => ['https://amazon.com/dune']])
            ->assertCreated()
            ->assertJsonPath('data.links.0', 'https://amazon.com/dune')
            ->json('data.id');
        $second = $this->postJson('/api/gifts/items', ['title' => 'Socks'])->assertCreated()->json('data.id');

        // New items go to the bottom.
        $this->getJson('/api/gifts/items')->assertJsonPath('data.*.id', [$first, $second]);

        $this->patchJson("/api/gifts/items/{$second}", ['title' => 'Wool socks', 'notes' => 'Size 10'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Wool socks')
            ->assertJsonPath('data.category', null);

        $this->deleteJson("/api/gifts/items/{$first}")->assertNoContent();
        $this->getJson('/api/gifts/items')->assertJsonPath('data.*.id', [$second]);
    }

    public function test_links_must_be_web_addresses(): void
    {
        $this->actingAsProfile(Profile::factory()->create());

        $this->postJson('/api/gifts/items', ['title' => 'X', 'links' => ['javascript:alert(1)']])
            ->assertJsonValidationErrors('links.0');
    }

    public function test_reordering(): void
    {
        $me = Profile::factory()->create();
        [$a, $b, $c] = GiftItem::factory()->for($me)->count(3)->sequence(fn ($s) => ['position' => $s->index])->create();
        $this->actingAsProfile($me);

        $this->putJson('/api/gifts/items/order', ['ids' => [$c->id, $a->id, $b->id]])->assertNoContent();

        $this->getJson('/api/gifts/items')->assertJsonPath('data.*.id', [$c->id, $a->id, $b->id]);
    }

    public function test_you_cannot_touch_someone_elses_items(): void
    {
        $theirs = GiftItem::factory()->create();
        $me = Profile::factory()->for($theirs->profile->user)->create(); // even on the same account
        $this->actingAsProfile($me);

        $this->patchJson("/api/gifts/items/{$theirs->id}", ['title' => 'Hacked'])->assertNotFound();
        $this->deleteJson("/api/gifts/items/{$theirs->id}")->assertNotFound();
        $this->putJson('/api/gifts/items/order', ['ids' => [$theirs->id]])->assertNotFound();
    }

    public function test_marking_received_and_undoing_it(): void
    {
        $item = GiftItem::factory()->create();
        $this->actingAsProfile($item->profile);

        $this->patchJson("/api/gifts/items/{$item->id}", ['received' => true])->assertJsonPath('data.received', true);
        $this->patchJson("/api/gifts/items/{$item->id}", ['received' => false])->assertJsonPath('data.received', false);
    }

    public function test_wishlist_links(): void
    {
        $me = Profile::factory()->create();
        $this->actingAsProfile($me);

        $id = $this->postJson('/api/gifts/wishlists', ['label' => 'Steam', 'url' => 'https://steamcommunity.com/id/kam/wishlist'])
            ->assertCreated()
            ->json('data.id');
        $this->getJson('/api/gifts/wishlists')->assertJsonPath('data.0.label', 'Steam');
        $this->deleteJson("/api/gifts/wishlists/{$id}")->assertNoContent();

        $other = GiftWishlist::factory()->create();
        $this->deleteJson("/api/gifts/wishlists/{$other->id}")->assertNotFound();
    }
}
