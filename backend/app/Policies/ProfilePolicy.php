<?php

namespace App\Policies;

use App\Auth\ActiveProfile;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Profile rules act on the profile using this device, not just the account:
 * admins manage the account's profiles; members may only rename themselves.
 */
class ProfilePolicy
{
    public function __construct(private ActiveProfile $activeProfile) {}

    /**
     * The first profile (first-time setup) needs no active profile; after
     * that only an admin adds profiles.
     */
    public function create(User $user): Response
    {
        if (! $user->profiles()->exists()) {
            return Response::allow();
        }

        return $this->activeIsAdmin()
            ? Response::allow()
            : Response::deny('Only an admin profile can add profiles.');
    }

    public function select(User $user, Profile $profile): Response
    {
        return $this->owns($user, $profile);
    }

    /**
     * Photos are seen by the account's own profiles and by families it shares.
     * Needs no active profile: the picker shows them.
     */
    public function viewAvatar(User $user, Profile $profile): Response
    {
        if ($profile->user_id === $user->id) {
            return Response::allow();
        }

        $sharesFamily = $user->groups()
            ->whereHas('members', fn ($members) => $members->whereKey($profile->user_id))
            ->exists();

        return $sharesFamily ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Profile $profile): Response
    {
        $owns = $this->owns($user, $profile);
        if ($owns->denied()) {
            return $owns;
        }

        return $this->activeIsAdmin() || $this->activeProfile->get()?->is($profile)
            ? Response::allow()
            : Response::deny('Only an admin profile can edit other profiles.');
    }

    public function changeRole(User $user, Profile $profile): Response
    {
        $owns = $this->owns($user, $profile);
        if ($owns->denied()) {
            return $owns;
        }

        return $this->activeIsAdmin()
            ? Response::allow()
            : Response::deny('Only an admin profile can change roles.');
    }

    public function delete(User $user, Profile $profile): Response
    {
        $owns = $this->owns($user, $profile);
        if ($owns->denied()) {
            return $owns;
        }
        if (! $this->activeIsAdmin()) {
            return Response::deny('Only an admin profile can delete profiles.');
        }

        return $this->activeProfile->get()?->is($profile)
            ? Response::deny("You can't delete the profile you're using.")
            : Response::allow();
    }

    /**
     * Another account's profile looks like it doesn't exist.
     */
    private function owns(User $user, Profile $profile): Response
    {
        return $profile->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    private function activeIsAdmin(): bool
    {
        return $this->activeProfile->get()?->isAdmin() ?? false;
    }
}
