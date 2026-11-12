<?php

namespace App\Models;

use App\Enums\GroupRole;
use Database\Factories\GroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A set of accounts that share with each other; shown as a "family" in the app.
 * Managed by admin accounts, in the app or with the groups:* commands.
 */
#[Fillable(['name'])]
class Group extends Model
{
    /** @use HasFactory<GroupFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<User, $this, GroupMembership, 'membership'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_memberships')
            ->using(GroupMembership::class)
            ->as('membership')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<GroupInvite, $this>
     */
    public function invites(): HasMany
    {
        return $this->hasMany(GroupInvite::class);
    }

    /**
     * Add an account, or change its role if it is already a member.
     */
    public function addMember(User $user, GroupRole $role = GroupRole::Member): void
    {
        $this->members()->syncWithoutDetaching([$user->id => ['role' => $role]]);
    }
}
