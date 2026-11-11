<?php

namespace App\Auth;

use App\Mail\LoginCodeMail;
use App\Models\LoginChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Issues and redeems emailed sign-in challenges (a 6-digit code plus a link).
 *
 * Secrets are stored only as HMAC-SHA256 hashes keyed with APP_KEY, are
 * single-use, and expire quickly. Callers never learn whether an email has
 * an account: issue() behaves the same either way.
 */
class PasswordlessLogin
{
    /**
     * Email a fresh code and link to the account for this address, if one
     * exists. Any earlier pending challenge for the account is discarded.
     */
    public function issue(string $email): void
    {
        $user = $this->findUser($email);

        if ($user === null) {
            return;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $token = Str::random(64);

        DB::transaction(function () use ($user, $code, $token): void {
            $user->loginChallenges()->delete();
            $user->loginChallenges()->create([
                'code_hash' => $this->hash($code),
                'token_hash' => $this->hash($token),
                'code_expires_at' => now()->addMinutes(config('auth.passwordless.code_ttl')),
                'link_expires_at' => now()->addMinutes(config('auth.passwordless.link_ttl')),
            ]);
        });

        Mail::to($user->email)->queue(new LoginCodeMail(
            code: $code,
            url: url('/auth/verify?token='.$token),
            expiresInMinutes: config('auth.passwordless.code_ttl'),
        ));
    }

    /**
     * Redeem a typed code. Returns the user on success, null otherwise.
     * Each wrong guess counts against the challenge; once the limit is
     * reached the challenge is deleted and a new code must be requested.
     */
    public function redeemCode(string $email, string $code): ?User
    {
        $user = $this->findUser($email);

        if ($user === null) {
            return null;
        }

        $maxAttempts = config('auth.passwordless.max_attempts');

        $challenge = $user->loginChallenges()
            ->where('code_expires_at', '>', now())
            ->where('attempts', '<', $maxAttempts)
            ->latest('id')
            ->first();

        if ($challenge === null) {
            return null;
        }

        if (! hash_equals($challenge->code_hash, $this->hash($code))) {
            LoginChallenge::query()->whereKey($challenge->id)->increment('attempts');
            LoginChallenge::query()->whereKey($challenge->id)->where('attempts', '>=', $maxAttempts)->delete();

            return null;
        }

        return $this->consume($challenge) ? $this->markVerified($user) : null;
    }

    /**
     * Redeem the token from an emailed link. Returns the user on success.
     */
    public function redeemLink(string $token): ?User
    {
        $challenge = LoginChallenge::query()
            ->where('token_hash', $this->hash($token))
            ->where('link_expires_at', '>', now())
            ->first();

        if ($challenge === null || ! $this->consume($challenge)) {
            return null;
        }

        return $this->markVerified($challenge->user);
    }

    /**
     * Delete the challenge; false if a concurrent request already used it.
     */
    private function consume(LoginChallenge $challenge): bool
    {
        return LoginChallenge::query()->whereKey($challenge->id)->delete() === 1;
    }

    private function markVerified(User $user): User
    {
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    private function findUser(string $email): ?User
    {
        return User::query()->where('email', Str::lower(trim($email)))->first();
    }

    private function hash(string $secret): string
    {
        return hash_hmac('sha256', $secret, config('app.key'));
    }
}
