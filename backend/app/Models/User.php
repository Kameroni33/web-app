<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A login: an email address that receives sign-in codes. It owns no data;
 * profiles (added later) do.
 */
#[Fillable(['email'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * The apps this user can open, in display order.
     *
     * Everyone gets every app for now. Per-user or per-group access goes
     * here once groups exist.
     *
     * @return list<array{key: string, name: string, description: string}>
     */
    public function availableApps(): array
    {
        return collect(config('apps'))
            ->map(fn (array $app, string $key): array => ['key' => $key, ...$app])
            ->values()
            ->all();
    }

    /**
     * @return HasMany<LoginChallenge, $this>
     */
    public function loginChallenges(): HasMany
    {
        return $this->hasMany(LoginChallenge::class);
    }
}
