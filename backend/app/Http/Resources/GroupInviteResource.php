<?php

namespace App\Http\Resources;

use App\Models\GroupInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An invite as admins see it. The link itself is never stored, so it is
 * not here; it comes back once, when the invite is created.
 *
 * @mixin GroupInvite
 */
class GroupInviteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status(),
            'uses' => $this->uses,
            'max_uses' => $this->max_uses,
            'expires_at' => $this->expires_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
