<?php

namespace App\Http\Resources\Gifts;

use App\Models\GiftItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An item as its owner sees it: never anything about claims.
 *
 * @mixin GiftItem
 */
class GiftItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'links' => $this->links,
            'category' => $this->category,
            'notes' => $this->notes,
            'received' => $this->received_at !== null,
        ];
    }
}
