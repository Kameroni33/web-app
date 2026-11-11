<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateLoginCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_login_with_a_normalised_email(): void
    {
        $this->artisan('app:create-login', ['email' => '  Mom@Example.COM '])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'mom@example.com']);
    }

    public function test_is_idempotent(): void
    {
        User::factory()->create(['email' => 'mom@example.com']);

        $this->artisan('app:create-login', ['email' => 'mom@example.com'])->assertSuccessful();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_rejects_an_invalid_email(): void
    {
        $this->artisan('app:create-login', ['email' => 'not-an-email'])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
