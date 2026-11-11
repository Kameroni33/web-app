<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('app:create-login {email : Email address that will receive sign-in codes}')]
#[Description('Create a login so that email address can sign in (there is no public signup)')]
class CreateLogin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->fails()) {
            $this->components->error("\"{$email}\" is not a valid email address.");

            return self::FAILURE;
        }

        $user = User::query()->firstOrCreate(['email' => $email]);

        $user->wasRecentlyCreated
            ? $this->components->info("Created login for {$email}.")
            : $this->components->warn("A login for {$email} already exists.");

        return self::SUCCESS;
    }
}
