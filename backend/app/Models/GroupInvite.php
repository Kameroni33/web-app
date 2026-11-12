<?php

namespace App\Models;

use Database\Factories\GroupInviteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link that lets whoever has it join a group. See App\Groups\GroupInvites.
 */
#[Fillable(['token_hash', 'max_uses', 'expires_at', 'created_by'])]
class GroupInvite extends Model
{
    /** @use HasFactory<GroupInviteFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_uses' => 'integer',
            'uses' => 'integer',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Group, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Not revoked, not expired, and not used up.
     *
     * @param  Builder<GroupInvite>  $query
     */
    public function scopeUsable(Builder $query): void
    {
        $query->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->where(fn (Builder $q) => $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses'));
    }

    /**
     * active, revoked, expired or used_up.
     */
    public function status(): string
    {
        return match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->expires_at->isPast() => 'expired',
            $this->max_uses !== null && $this->uses >= $this->max_uses => 'used_up',
            default => 'active',
        };
    }
}
