<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The apps the signed-in user can pick from on the landing page.
 */
class AvailableAppController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()->availableApps()]);
    }
}
