<?php

namespace App\Http\Controllers\Gifts;

use App\Auth\ActiveProfile;
use App\Gifts\GiftAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\Gifts\ViewerGiftItemResource;
use App\Models\GiftItem;
use App\Models\Profile;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * "I'm getting this" on someone else's item, and taking it back.
 */
class ClaimController extends Controller
{
    public function __construct(private ActiveProfile $activeProfile, private GiftAccess $access) {}

    public function store(GiftItem $item): ViewerGiftItemResource
    {
        $me = $this->viewableBy($item);
        abort_if($item->received_at !== null, 404);

        if ($item->claim === null) {
            try {
                $item->claim()->create(['profile_id' => $me->id]);
            } catch (UniqueConstraintViolationException) {
                // Someone claimed it a moment ago; the response says so.
            }
        }

        return new ViewerGiftItemResource($item->load('claim'));
    }

    /**
     * Works on removed (soft-deleted) items too, so a claimer can let go.
     */
    public function destroy(int $item): ViewerGiftItemResource
    {
        $item = GiftItem::withTrashed()->findOrFail($item);
        $me = $this->viewableBy($item);

        $item->claim()->where('profile_id', $me->id)->delete();

        return new ViewerGiftItemResource($item->load('claim'));
    }

    private function viewableBy(GiftItem $item): Profile
    {
        $me = $this->activeProfile->get();
        abort_unless($this->access->canView($me, $item->profile), 404);

        return $me;
    }
}
