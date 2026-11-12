<?php

namespace App\Http\Resources\Gifts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftWishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
