<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('accounts:admin {email : Account email} {--revoke : Take admin away instead}')]
#[Description('Make an account an admin account (manages families and invites in the app)')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $user = User::query()->firstWhere('email', Str::lower(trim($this->argument('email'))));
        if ($user === null) {
            $this->components->error('No account with that email. Create it with accounts:create.');

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => ! $this->option('revoke')])->save();

        $user->is_admin
            ? $this->components->info("{$user->email} is now an admin account. Its admin profile can manage families in Settings.")
            : $this->components->info("{$user->email} is no longer an admin account.");

        return self::SUCCESS;
    }
}
