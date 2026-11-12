<?php

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('accounts:list')]
#[Description('List accounts with their profiles and groups')]
class ListAccounts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $accounts = User::query()->with(['profiles', 'groups'])->orderBy('email')->get();

        $this->table(['Email', 'Admin', 'Profiles (* = admin)', 'Groups', 'First sign-in'], $accounts->map(fn (User $user): array => [
            $user->email,
            $user->is_admin ? 'yes' : '',
            $user->profiles->map(fn (Profile $profile): string => $profile->name.($profile->isAdmin() ? '*' : ''))->join(', ') ?: '(none yet)',
            $user->groups->map(fn (Group $group): string => "{$group->name} (#{$group->id})")->join(', ') ?: '—',
            $user->email_verified_at?->toDateString() ?? 'never',
        ]));

        return self::SUCCESS;
    }
}
