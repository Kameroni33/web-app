<?php

namespace App\Http\Controllers;

use App\Auth\ActiveProfile;
use App\Enums\ProfileRole;
use App\Http\Requests\StoreProfileRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * The signed-in account's profiles. Rules live in ProfilePolicy.
 */
class ProfileController extends Controller
{
    public function __construct(private ActiveProfile $activeProfile) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ProfileResource::collection($request->user()->profiles);
    }

    /**
     * The account's first profile is its admin and becomes active right away.
     */
    public function store(StoreProfileRequest $request): ProfileResource
    {
        $user = $request->user();
        Gate::authorize('create', Profile::class);

        $isFirst = ! $user->profiles()->exists();
        $profile = $user->profiles()->create([
            'name' => $request->validated('name'),
            'role' => $isFirst ? ProfileRole::Admin : $request->enum('role', ProfileRole::class) ?? ProfileRole::Member,
        ]);

        if ($isFirst) {
            $this->activeProfile->set($profile);
        }

        return new ProfileResource($profile);
    }

    public function update(UpdateProfileRequest $request, Profile $profile): ProfileResource
    {
        Gate::authorize('update', $profile);

        if ($request->has('role')) {
            Gate::authorize('changeRole', $profile);
            $role = $request->enum('role', ProfileRole::class);

            if ($profile->isAdmin() && $role !== ProfileRole::Admin && $this->isLastAdmin($profile)) {
                throw ValidationException::withMessages(['role' => 'An account needs at least one admin profile.']);
            }
            $profile->role = $role;
        }

        $profile->fill($request->safe()->only('name'))->save();

        return new ProfileResource($profile);
    }

    public function destroy(Profile $profile): Response
    {
        Gate::authorize('delete', $profile);
        $profile->delete();

        return response()->noContent();
    }

    private function isLastAdmin(Profile $profile): bool
    {
        return ! $profile->user->profiles()
            ->where('role', ProfileRole::Admin)
            ->whereKeyNot($profile->id)
            ->exists();
    }
}
