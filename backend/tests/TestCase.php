<?php

namespace Tests;

use App\Auth\ActiveProfile;
use App\Models\Profile;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sign in as the profile's account with that profile chosen on this
     * "device", the way the app's requests arrive (stateful SPA session).
     */
    protected function actingAsProfile(Profile $profile): static
    {
        return $this->withHeader('Referer', 'http://localhost')
            ->actingAs($profile->user)
            ->withSession([ActiveProfile::SESSION_KEY => $profile->id]);
    }
}
