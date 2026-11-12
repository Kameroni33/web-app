<?php

namespace App\Http\Controllers\Auth;

use App\Auth\ActiveProfile;
use App\Auth\PasswordlessLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestLoginCodeRequest;
use App\Http\Requests\Auth\VerifyLoginCodeRequest;
use App\Http\Requests\Auth\VerifyLoginLinkRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(
        private PasswordlessLogin $passwordless,
        private ActiveProfile $activeProfile,
    ) {}

    /**
     * Email a sign-in code and link. The response is identical whether or
     * not the address has an account, so it can't be used to probe emails.
     */
    public function requestCode(RequestLoginCodeRequest $request): JsonResponse
    {
        $this->passwordless->issue($request->validated('email'));

        return response()->json([
            'message' => 'If that address has an account, we sent it a sign-in code.',
        ], 202);
    }

    public function verifyCode(VerifyLoginCodeRequest $request): UserResource
    {
        $user = $this->passwordless->redeemCode(
            $request->validated('email'),
            $request->validated('code'),
        );

        return $this->startSession($request, $user, 'code', 'That code is wrong or has expired.');
    }

    /**
     * Called by the link page's "Sign in" button (a POST, so link scanners
     * that GET the URL can't use up the token).
     */
    public function verifyLink(VerifyLoginLinkRequest $request): UserResource
    {
        $user = $this->passwordless->redeemLink($request->validated('token'));

        return $this->startSession($request, $user, 'token', 'This sign-in link has expired or was already used.');
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function startSession(Request $request, ?User $user, string $field, string $error): UserResource
    {
        if ($user === null) {
            throw ValidationException::withMessages([$field => $error]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // One profile: use it. Several: the app asks who's using this device.
        $this->activeProfile->forget();
        $profiles = $user->profiles()->limit(2)->get();
        if ($profiles->count() === 1) {
            $this->activeProfile->set($profiles->first());
        }

        return new UserResource($user);
    }
}
