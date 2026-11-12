<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileAvatarRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Profiles\AvatarImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Profile photos. Anyone who can edit a profile can change its photo.
 */
class ProfileAvatarController extends Controller
{
    /**
     * Served with the version in the URL, so browsers may cache it for good.
     */
    public function show(Profile $profile): Response
    {
        Gate::authorize('viewAvatar', $profile);

        $avatar = $profile->avatar;
        abort_if($avatar === null, 404);

        return response($avatar->image, 200, [
            'Content-Type' => $avatar->mime_type,
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'",
        ]);
    }

    public function update(UpdateProfileAvatarRequest $request, Profile $profile, AvatarImage $images): ProfileResource
    {
        Gate::authorize('update', $profile);

        $image = $images->fromFile($request->file('image')->getRealPath());

        DB::transaction(function () use ($profile, $image) {
            $profile->avatar()->updateOrCreate([], [
                'mime_type' => AvatarImage::MIME_TYPE,
                'image' => $image,
            ]);
            $profile->forceFill(['avatar_version' => Str::random(12)])->save();
        });

        return new ProfileResource($profile->refresh());
    }

    public function destroy(Profile $profile): ProfileResource
    {
        Gate::authorize('update', $profile);

        DB::transaction(function () use ($profile) {
            $profile->avatar()->delete();
            $profile->forceFill(['avatar_version' => null])->save();
        });

        return new ProfileResource($profile);
    }
}
