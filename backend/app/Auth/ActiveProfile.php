<?php

namespace App\Auth;

use App\Models\Profile;
use Illuminate\Http\Request;

/**
 * Which of the account's profiles is using this device right now.
 *
 * Stored in the server-side session, so each signed-in device picks its own
 * profile, and a profile deleted elsewhere simply stops resolving.
 */
class ActiveProfile
{
    public const SESSION_KEY = 'active_profile_id';

    private const REQUEST_CACHE_KEY = 'active_profile';

    /**
     * Always the current request: instances can outlive one (controllers
     * are reused between requests in tests and long-running servers).
     */
    private function request(): Request
    {
        return app('request');
    }

    public function get(): ?Profile
    {
        $user = $this->request()->user();
        if ($user === null || ! $this->request()->hasSession()) {
            return null;
        }

        if (! $this->request()->attributes->has(self::REQUEST_CACHE_KEY)) {
            $id = $this->request()->session()->get(self::SESSION_KEY);
            $this->request()->attributes->set(
                self::REQUEST_CACHE_KEY,
                $id === null ? null : $user->profiles()->whereKey($id)->first(),
            );
        }

        return $this->request()->attributes->get(self::REQUEST_CACHE_KEY);
    }

    public function set(Profile $profile): void
    {
        $this->request()->session()->put(self::SESSION_KEY, $profile->id);
        $this->request()->attributes->set(self::REQUEST_CACHE_KEY, $profile);
    }

    public function forget(): void
    {
        $this->request()->session()->forget(self::SESSION_KEY);
        $this->request()->attributes->remove(self::REQUEST_CACHE_KEY);
    }
}
