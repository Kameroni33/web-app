<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('groups:remove-member {group : Group id} {email : Account email}')]
#[Description('Remove an account from a group')]
class RemoveGroupMember extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $group = Group::query()->find($this->argument('group'));
        $user = User::query()->firstWhere('email', Str::lower(trim($this->argument('email'))));

        if ($group === null || $user === null || $group->members()->detach($user) === 0) {
            $this->components->error('That account is not a member of that group.');

            return self::FAILURE;
        }

        $this->components->info("Removed {$user->email} from {$group->name}.");

        return self::SUCCESS;
    }
}
