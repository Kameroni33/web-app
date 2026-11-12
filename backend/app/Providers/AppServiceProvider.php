<?php

namespace App\Providers;

use App\Auth\ActiveProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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
        $this->configureGates();
    }

    /**
     * Site-wide admin powers need an admin account (accounts:admin) and its
     * admin profile, so a child's profile on that account doesn't get them.
     */
    private function configureGates(): void
    {
        Gate::define('manage-groups', function (User $user): Response {
            if (! $user->is_admin) {
                return Response::deny('Only admin accounts can manage families.');
            }

            return app(ActiveProfile::class)->get()?->isAdmin()
                ? Response::allow()
                : Response::deny('Switch to the account’s admin profile to manage families.');
        });
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

        RateLimiter::for('invite', fn (Request $request): Limit => Limit::perMinute(20)->by('invite:ip:'.$request->ip()));

        RateLimiter::for('login-verify', fn (Request $request): Limit => Limit::perMinute(10)->by('login-verify:ip:'.$request->ip()));
    }
}
