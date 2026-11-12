<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum only starts a session for requests from a stateful origin.
        $this->withHeader('Referer', 'http://localhost');
    }

    public function test_lists_only_the_users_own_devices_without_session_ids(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->insertSession('phone', $user, 'iPhone Safari');
        $this->insertSession('laptop', $user, 'Firefox');
        $this->insertSession('elsewhere', $other, 'Chrome');

        $response = $this->actingAs($user)->getJson('/api/sessions')->assertOk();

        $response->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing(
            ['iPhone Safari', 'Firefox'],
            array_column($response->json('data'), 'user_agent'),
        );
        $this->assertArrayNotHasKey('id', $response->json('data.0'));
    }

    public function test_signing_out_other_devices_keeps_other_users_sessions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->insertSession('phone', $user);
        $this->insertSession('laptop', $user);
        $this->insertSession('elsewhere', $other);

        $this->actingAs($user)->deleteJson('/api/sessions/others')->assertNoContent();

        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'elsewhere']);
    }

    public function test_device_endpoints_require_authentication(): void
    {
        $this->getJson('/api/sessions')->assertUnauthorized();
        $this->deleteJson('/api/sessions/others')->assertUnauthorized();
    }

    private function insertSession(string $id, User $user, string $userAgent = 'Browser'): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.7',
            'user_agent' => $userAgent,
            'payload' => '',
            'last_activity' => now()->getTimestamp(),
        ]);
    }
}
