<?php

namespace App\Gifts;

use App\Models\Group;
use App\Models\Profile;
use Illuminate\Support\Collection;

/**
 * Whose lists a profile may see: the other profiles on its own account,
 * and every profile of the accounts it shares a family with.
 */
class GiftAccess
{
    public function canView(Profile $viewer, Profile $owner): bool
    {
        if ($viewer->is($owner)) {
            return false;
        }

        return $viewer->user_id === $owner->user_id || $this->sharesFamily($viewer->user_id, $owner->user_id);
    }

    /**
     * Families (with their people) the viewer's account belongs to, leaving
     * out the viewer's own account: those profiles are listed separately.
     *
     * @return Collection<int, array{id: int, name: string, people: Collection<int, Profile>}>
     */
    public function families(Profile $viewer): Collection
    {
        return $viewer->user->groups()
            ->with(['members' => fn ($members) => $members->whereKeyNot($viewer->user_id)->with('profiles')])
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'id' => $group->id,
                'name' => $group->name,
                'people' => $group->members->flatMap->profiles->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
            ])
            ->filter(fn (array $family) => $family['people']->isNotEmpty())
            ->values();
    }

    /**
     * The other profiles on the viewer's own account.
     *
     * @return Collection<int, Profile>
     */
    public function sameAccount(Profile $viewer): Collection
    {
        return $viewer->user->profiles()->whereKeyNot($viewer->id)->get();
    }

    private function sharesFamily(int $userId, int $otherUserId): bool
    {
        return Group::query()
            ->whereHas('members', fn ($members) => $members->whereKey($userId))
            ->whereHas('members', fn ($members) => $members->whereKey($otherUserId))
            ->exists();
    }
}
