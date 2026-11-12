<?php

namespace App\Groups;

use App\Models\Group;
use App\Models\GroupInvite;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Invite links: whoever has the link may join the group, until it expires,
 * is used up, or is revoked. Used by both the admin UI and the groups:*
 * commands.
 *
 * The token appears only in the link; the database keeps an HMAC-SHA256 of
 * it keyed with APP_KEY (like sign-in codes), so a leaked database can't be
 * turned into working links.
 */
class GroupInvites
{
    /**
     * @param  int|null  $maxUses  null for unlimited
     * @return array{0: GroupInvite, 1: string} the invite and its link (shown once)
     */
    public function create(Group $group, ?User $createdBy, ?int $maxUses = 1, int $days = 7): array
    {
        $token = bin2hex(random_bytes(24));

        $invite = $group->invites()->create([
            'token_hash' => $this->hash($token),
            'max_uses' => $maxUses,
            'expires_at' => now()->addDays($days),
            'created_by' => $createdBy?->id,
        ]);

        return [$invite, $this->url($token)];
    }

    /**
     * The invite for a link token, if it can still be used.
     */
    public function findUsable(string $token): ?GroupInvite
    {
        return GroupInvite::query()->usable()->with('group')->firstWhere('token_hash', $this->hash($token));
    }

    /**
     * Join the invite's group. Returns the group, or null if the link can't
     * be used. Joining a group you're already in doesn't use up the link.
     */
    public function accept(string $token, User $user): ?Group
    {
        return DB::transaction(function () use ($token, $user): ?Group {
            $invite = $this->findUsable($token);
            if ($invite === null) {
                return null;
            }

            $group = $invite->group;
            if ($group->members()->whereKey($user->id)->exists()) {
                return $group;
            }

            // Atomic: two people racing for the last use can't both get in.
            $claimed = GroupInvite::query()->usable()->whereKey($invite->id)->increment('uses');
            if ($claimed === 0) {
                return null;
            }

            $group->addMember($user);

            return $group;
        });
    }

    public function revoke(GroupInvite $invite): void
    {
        $invite->forceFill(['revoked_at' => $invite->revoked_at ?? now()])->save();
    }

    public function url(string $token): string
    {
        return url('/invite/'.$token);
    }

    private function hash(string $token): string
    {
        return hash_hmac('sha256', $token, config('app.key'));
    }
}
