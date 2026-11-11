<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Sign-in throttles, keyed per email and per IP. Limits apply whether or
     * not the email has an account, so a 429 reveals nothing about it.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login-request', fn (Request $request): array => [
            Limit::perHour(5)->by('login-request:email:'.Str::lower(trim((string) $request->input('email')))),
            Limit::perHour(30)->by('login-request:ip:'.$request->ip()),
        ]);

        RateLimiter::for('login-verify', fn (Request $request): Limit => Limit::perMinute(10)->by('login-verify:ip:'.$request->ip()));
    }
}
