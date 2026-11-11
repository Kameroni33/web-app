<?php

namespace App\Models;

use App\Enums\GroupRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * An account's membership in a group, with its role there.
 */
class GroupMembership extends Pivot
{
    protected $table = 'group_memberships';

    public $incrementing = true;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => GroupRole::class,
        ];
    }
}
