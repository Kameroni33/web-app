<?php

namespace App\Http\Controllers\Gifts;

use App\Auth\ActiveProfile;
use App\Gifts\GiftAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\Gifts\GiftWishlistResource;
use App\Http\Resources\Gifts\ViewerGiftItemResource;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;

/**
 * Someone else's list. Received items are hidden; items the owner deleted
 * stay visible (marked removed) only to whoever had claimed them.
 */
class ListController extends Controller
{
    public function __invoke(Profile $profile, ActiveProfile $activeProfile, GiftAccess $access): JsonResponse
    {
        $me = $activeProfile->get();
        abort_unless($access->canView($me, $profile), 404);

        $items = $profile->giftItems()
            ->withTrashed()
            ->whereNull('received_at')
            ->where(fn ($query) => $query
                ->whereNull('deleted_at')
                ->orWhereHas('claim', fn ($claim) => $claim->where('profile_id', $me->id)))
            ->with('claim')
            ->ordered()
            ->get();

        return response()->json(['data' => [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'avatar_url' => $profile->avatarUrl(),
                'same_account' => $profile->user_id === $me->user_id,
            ],
            'items' => ViewerGiftItemResource::collection($items),
            'wishlists' => GiftWishlistResource::collection($profile->giftWishlists),
        ]]);
    }
}
