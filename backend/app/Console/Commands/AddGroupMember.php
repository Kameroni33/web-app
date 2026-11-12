<?php

namespace App\Console\Commands;

use App\Enums\GroupRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('groups:add-member
    {group : Group id (see groups:list)}
    {email : Email of an existing account (see accounts:create)}
    {--role=member : member or manager}')]
#[Description('Add an account to a group, or change its role there')]
class AddGroupMember extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $group = Group::query()->find($this->argument('group'));
        $user = User::query()->firstWhere('email', Str::lower(trim($this->argument('email'))));
        $role = GroupRole::tryFrom((string) $this->option('role'));

        if ($group === null || $user === null || $role === null) {
            $this->components->error(match (true) {
                $group === null => 'No group with that id. See groups:list.',
                $user === null => 'No account with that email. Create it with accounts:create.',
                default => 'Role must be one of: '.implode(', ', array_column(GroupRole::cases(), 'value')).'.',
            });

            return self::FAILURE;
        }

        $group->addMember($user, $role);
        $this->components->info("{$user->email} is a {$role->value} of {$group->name}.");

        return self::SUCCESS;
    }
}
