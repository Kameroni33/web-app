<?php

namespace App\Http\Controllers\Gifts;

use App\Auth\ActiveProfile;
use App\Gifts\GiftAccess;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;

/**
 * Whose lists the active profile can open: others on this account, then
 * each family.
 */
class PeopleController extends Controller
{
    public function __invoke(ActiveProfile $activeProfile, GiftAccess $access): JsonResponse
    {
        $me = $activeProfile->get();
        $person = fn (Profile $profile): array => [
            'id' => $profile->id,
            'name' => $profile->name,
            'avatar_url' => $profile->avatarUrl(),
        ];

        return response()->json(['data' => [
            'account' => $access->sameAccount($me)->map($person)->values(),
            'families' => $access->families($me)->map(fn (array $family) => [
                'id' => $family['id'],
                'name' => $family['name'],
                'people' => $family['people']->map($person)->values(),
            ]),
        ]]);
    }
}
