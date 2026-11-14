<?php

namespace App\Http\Resources\Gifts;

use App\Auth\ActiveProfile;
use App\Models\GiftItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Someone else's item. claim is "mine", "taken" (by someone, never who)
 * or null. removed: the owner deleted it after you claimed it.
 *
 * @mixin GiftItem
 */
class ViewerGiftItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewerId = app(ActiveProfile::class)->get()?->id;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'links' => $this->links,
            'category' => $this->category,
            'notes' => $this->notes,
            'claim' => match (true) {
                $this->claim === null => null,
                $this->claim->profile_id === $viewerId => 'mine',
                default => 'taken',
            },
            'removed' => $this->trashed(),
        ];
    }
}
