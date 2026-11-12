<?php

namespace App\Http\Controllers;

use App\Auth\PasswordlessLogin;
use App\Groups\GroupInvites;
use App\Http\Requests\RegisterFromInviteRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The public side of invite links (/invite/{token} in the app).
 *
 * Signing up is two steps on purpose: register only creates the account and
 * emails a sign-in code; joining happens after the person proves they own
 * the email. A link holder can't push someone else's account into a family.
 */
class InviteController extends Controller
{
    public function __construct(private GroupInvites $invites) {}

    public function show(Request $request, string $token): JsonResponse
    {
        $invite = $this->invites->findUsable($token) ?? $this->invalid();
        $user = $request->user('sanctum');

        return response()->json(['data' => [
            'group' => ['name' => $invite->group->name],
            'expires_at' => $invite->expires_at->toIso8601String(),
            'is_member' => $user !== null && $invite->group->members()->whereKey($user->id)->exists(),
        ]]);
    }

    /**
     * New here: create the account (if needed) and email a sign-in code.
     */
    public function register(RegisterFromInviteRequest $request, string $token, PasswordlessLogin $passwordless): JsonResponse
    {
        $this->invites->findUsable($token) ?? $this->invalid();

        $email = $request->validated('email');
        User::query()->firstOrCreate(['email' => $email]);
        $passwordless->issue($email);

        return response()->json(['message' => 'We sent you a sign-in code.'], 202);
    }

    /**
     * Signed in: join the family.
     */
    public function accept(Request $request, string $token): JsonResponse
    {
        $group = $this->invites->accept($token, $request->user()) ?? $this->invalid();

        return response()->json(['data' => ['id' => $group->id, 'name' => $group->name]]);
    }

    private function invalid(): never
    {
        abort(response()->json([
            'message' => 'This invite link has expired, was already used, or was cancelled. Ask for a new one.',
        ], 404));
    }
}
