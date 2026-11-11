<?php

namespace App\Http\Middleware;

use App\Auth\ActiveProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * App routes act as a profile. Without one chosen on this device, answer 409
 * so the app shows the profile picker (or first-time setup).
 */
class RequireProfile
{
    public function __construct(private ActiveProfile $activeProfile) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->activeProfile->get() === null) {
            return response()->json([
                'message' => 'Choose a profile first.',
                'code' => 'profile_required',
            ], 409);
        }

        return $next($request);
    }
}
