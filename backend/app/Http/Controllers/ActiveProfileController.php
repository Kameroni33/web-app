<?php

namespace App\Http\Controllers;

use App\Auth\ActiveProfile;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use Illuminate\Support\Facades\Gate;

/**
 * "Who's using the app?": pick the profile for this device's session.
 */
class ActiveProfileController extends Controller
{
    public function __invoke(Profile $profile, ActiveProfile $activeProfile): ProfileResource
    {
        Gate::authorize('select', $profile);
        $activeProfile->set($profile);

        return new ProfileResource($profile);
    }
}
