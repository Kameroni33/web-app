<?php

namespace App\Http\Resources;

use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A family as its members see it: names only, no emails.
 *
 * @mixin Group
 */
class GroupResource extends JsonResource
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
            'name' => $this->name,
            'members' => $this->members
                ->flatMap(fn (User $account) => $account->profiles)
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->map(fn (Profile $profile): array => ['id' => $profile->id, 'name' => $profile->name])
                ->values(),
        ];
    }
}
