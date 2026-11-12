<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppVersionTest extends TestCase
{
    public function test_api_responses_carry_the_server_version(): void
    {
        config(['app.version' => '2026.11.3']);

        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertHeader('X-App-Version', '2026.11.3');
    }

    public function test_the_configured_version_is_year_month_patch(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}\.([1-9]|1[0-2])\.\d+$/', config('app.version'));
    }
}
