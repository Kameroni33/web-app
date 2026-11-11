<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in user's devices (database sessions). Session ids are the
 * cookie secrets, so they are never returned.
 */
class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->session()->getId();

        $sessions = DB::table(config('session.table'))
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_active_at' => Carbon::createFromTimestamp($session->last_activity)->toIso8601String(),
                'is_current' => $session->id === $currentId,
            ]);

        return response()->json(['data' => $sessions]);
    }

    /**
     * "Sign out everywhere else": the recovery tool for a lost phone.
     */
    public function destroyOthers(Request $request): Response
    {
        DB::table(config('session.table'))
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return response()->noContent();
    }
}
