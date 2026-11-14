<?php

namespace App\Http\Controllers\Gifts;

use App\Auth\ActiveProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Reorders the active profile's list: ids, most wanted first.
 */
class ItemOrderController extends Controller
{
    public function __invoke(Request $request, ActiveProfile $activeProfile): Response
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['integer', 'distinct'],
        ]);
        $items = $activeProfile->get()->giftItems();

        abort_if((clone $items)->whereKey($validated['ids'])->count() !== count($validated['ids']), 404);

        DB::transaction(function () use ($items, $validated) {
            foreach ($validated['ids'] as $position => $id) {
                (clone $items)->whereKey($id)->update(['position' => $position]);
            }
        });

        return response()->noContent();
    }
}
