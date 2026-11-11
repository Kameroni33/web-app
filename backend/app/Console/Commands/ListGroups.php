<?php

namespace App\Console\Commands;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:list')]
#[Description('List groups and their member accounts')]
class ListGroups extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $groups = Group::query()->with('members')->orderBy('id')->get();

        $this->table(['Id', 'Name', 'Members'], $groups->map(fn (Group $group): array => [
            $group->id,
            $group->name,
            $group->members
                ->map(fn (User $user): string => $user->email.($user->membership->role === GroupRole::Member ? '' : " ({$user->membership->role->value})"))
                ->join(', ') ?: '—',
        ]));

        return self::SUCCESS;
    }
}
