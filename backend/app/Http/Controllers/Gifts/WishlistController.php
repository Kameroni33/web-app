<?php

namespace App\Http\Controllers\Gifts;

use App\Auth\ActiveProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gifts\StoreGiftWishlistRequest;
use App\Http\Resources\Gifts\GiftWishlistResource;
use App\Models\GiftWishlist;
use App\Models\Profile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * The active profile's links to wish lists on other sites.
 */
class WishlistController extends Controller
{
    public function __construct(private ActiveProfile $activeProfile) {}

    public function index(): AnonymousResourceCollection
    {
        return GiftWishlistResource::collection($this->me()->giftWishlists);
    }

    public function store(StoreGiftWishlistRequest $request): GiftWishlistResource
    {
        return new GiftWishlistResource($this->me()->giftWishlists()->create($request->validated()));
    }

    public function update(StoreGiftWishlistRequest $request, GiftWishlist $wishlist): GiftWishlistResource
    {
        $this->authorizeOwner($wishlist);
        $wishlist->update($request->validated());

        return new GiftWishlistResource($wishlist);
    }

    public function destroy(GiftWishlist $wishlist): Response
    {
        $this->authorizeOwner($wishlist);
        $wishlist->delete();

        return response()->noContent();
    }

    private function me(): Profile
    {
        return $this->activeProfile->get();
    }

    private function authorizeOwner(GiftWishlist $wishlist): void
    {
        abort_unless($wishlist->profile_id === $this->me()->id, 404);
    }
}
