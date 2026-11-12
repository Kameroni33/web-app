<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\GroupInviteResource;
use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A group as admin accounts see it: member emails included.
 *
 * @mixin Group
 */
class AdminGroupResource extends JsonResource
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
            'member_count' => $this->whenCounted('members'),
            'members' => $this->whenLoaded('members', fn () => $this->members->map(fn (User $member): array => [
                'id' => $member->id,
                'email' => $member->email,
                'role' => $member->membership->role,
                'profiles' => $member->profiles->map(fn (Profile $profile): string => $profile->name)->values(),
            ])),
            'invites' => GroupInviteResource::collection($this->whenLoaded('invites')),
        ];
    }
}
