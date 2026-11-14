<?php

namespace App\Http\Controllers\Gifts;

use App\Auth\ActiveProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Gifts\StoreGiftItemRequest;
use App\Http\Resources\Gifts\GiftItemResource;
use App\Models\GiftItem;
use App\Models\Profile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * The active profile's own list. Received items stay here (the owner
 * clears them by hand) but are hidden from everyone else.
 */
class MyItemController extends Controller
{
    public function __construct(private ActiveProfile $activeProfile) {}

    public function index(): AnonymousResourceCollection
    {
        return GiftItemResource::collection($this->me()->giftItems()->ordered()->get());
    }

    /**
     * New items go to the bottom; the owner drags them up.
     */
    public function store(StoreGiftItemRequest $request): GiftItemResource
    {
        $item = $this->me()->giftItems()->create([
            ...$request->safe()->except('received'),
            'position' => ($this->me()->giftItems()->max('position') ?? -1) + 1,
        ]);

        return new GiftItemResource($item->refresh());
    }

    public function update(StoreGiftItemRequest $request, GiftItem $item): GiftItemResource
    {
        $this->authorizeOwner($item);

        $item->fill($request->safe()->except('received'));
        if ($request->has('received')) {
            $item->received_at = $request->boolean('received') ? ($item->received_at ?? now()) : null;
        }
        $item->save();

        return new GiftItemResource($item);
    }

    public function destroy(GiftItem $item): Response
    {
        $this->authorizeOwner($item);
        $item->delete();

        return response()->noContent();
    }

    private function me(): Profile
    {
        return $this->activeProfile->get();
    }

    private function authorizeOwner(GiftItem $item): void
    {
        abort_unless($item->profile_id === $this->me()->id, 404);
    }
}
