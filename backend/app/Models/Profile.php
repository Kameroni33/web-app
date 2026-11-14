<?php

namespace App\Models;

use App\Enums\ProfileRole;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A person using an account. App data (gift lists, recipes…) belongs here.
 */
#[Fillable(['name', 'role'])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ProfileRole::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasOne<ProfileAvatar, $this>
     */
    public function avatar(): HasOne
    {
        return $this->hasOne(ProfileAvatar::class);
    }

    /**
     * Versioned, so a new photo gets a new URL and old ones can be cached forever.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_version === null
            ? null
            : "/api/profiles/{$this->id}/avatar?v={$this->avatar_version}";
    }

    /**
     * @return HasMany<GiftItem, $this>
     */
    public function giftItems(): HasMany
    {
        return $this->hasMany(GiftItem::class);
    }

    /**
     * @return HasMany<GiftWishlist, $this>
     */
    public function giftWishlists(): HasMany
    {
        return $this->hasMany(GiftWishlist::class)->orderBy('id');
    }

    public function isAdmin(): bool
    {
        return $this->role === ProfileRole::Admin;
    }
}
