<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordlessLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        // Sanctum only starts a session for requests from a stateful origin.
        $this->withHeader('Referer', 'http://localhost');
    }

    public function test_unknown_email_gets_the_same_response_and_no_mail(): void
    {
        $known = User::factory()->create();

        $unknownResponse = $this->postJson('/api/auth/login-code', ['email' => 'nobody@example.com']);
        $knownResponse = $this->postJson('/api/auth/login-code', ['email' => $known->email]);

        $unknownResponse->assertAccepted();
        $this->assertSame($knownResponse->json(), $unknownResponse->json());
        Mail::assertQueuedCount(1);
    }

    public function test_requesting_a_code_emails_a_code_and_a_link(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login-code', ['email' => strtoupper($user->email)])->assertAccepted();

        $mail = $this->sentMail();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $mail->code);
        $this->assertStringContainsString('/auth/verify?token=', $mail->url);
        $this->assertDatabaseCount('login_challenges', 1);
        $this->assertDatabaseMissing('login_challenges', ['code_hash' => $mail->code]);
    }

    public function test_correct_code_signs_in_once(): void
    {
        $user = User::factory()->unverified()->create();
        $code = $this->requestCode($user)->code;

        $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseCount('login_challenges', 0);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertUnprocessable();
    }

    public function test_wrong_codes_are_limited_to_five_attempts(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user)->code;
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $wrong])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('code');
        }

        $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $code = $this->requestCode($user)->code;

        $this->travel(11)->minutes();

        $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $code])
            ->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_requesting_a_new_code_invalidates_the_previous_one(): void
    {
        $user = User::factory()->create();
        $first = $this->requestCode($user)->code;
        $second = $this->requestCode($user)->code;

        if ($first !== $second) {
            $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $first])
                ->assertUnprocessable();
        }

        $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $second])
            ->assertOk();
    }

    public function test_link_token_signs_in_once(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFrom($this->requestCode($user));

        $this->postJson('/api/auth/verify-link', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
        $this->assertAuthenticatedAs($user, 'web');

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/verify-link', ['token' => $token])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');
    }

    public function test_expired_link_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $this->tokenFrom($this->requestCode($user));

        $this->travel(16)->minutes();

        $this->postJson('/api/auth/verify-link', ['token' => $token])->assertUnprocessable();
        $this->assertGuest('web');
    }

    public function test_code_requests_are_rate_limited_per_email(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/login-code', ['email' => $user->email])->assertAccepted();
        }

        $this->postJson('/api/auth/login-code', ['email' => $user->email])->assertTooManyRequests();
    }

    public function test_me_requires_a_session_and_logout_ends_it(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $user = User::factory()->create();
        $code = $this->requestCode($user)->code;
        $this->postJson('/api/auth/verify-code', ['email' => $user->email, 'code' => $code])->assertOk();

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.email', $user->email);

        $this->postJson('/api/auth/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    private function requestCode(User $user): LoginCodeMail
    {
        $this->postJson('/api/auth/login-code', ['email' => $user->email])->assertAccepted();

        return $this->sentMail();
    }

    private function sentMail(): LoginCodeMail
    {
        $sent = Mail::queued(LoginCodeMail::class);
        $this->assertNotEmpty($sent, 'No login code mail was queued.');

        return $sent->last();
    }

    private function tokenFrom(LoginCodeMail $mail): string
    {
        parse_str((string) parse_url($mail->url, PHP_URL_QUERY), $query);

        return $query['token'];
    }
}
