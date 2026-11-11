<?php

namespace App\Console\Commands;

use App\Enums\ProfileRole;
use App\Models\Group;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Signature('accounts:create
    {email : Email address that will receive sign-in codes}
    {--name= : Name for the admin profile (otherwise asked on first sign-in)}
    {--group=* : Group id to join (repeatable); omit for an account in no family}')]
#[Description('Create an account so that email address can sign in (there is no public signup)')]
class CreateAccount extends Command
{
    /**
     * @var list<string>
     */
    protected $aliases = ['app:create-login'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = Str::lower(trim($this->argument('email')));
        $name = $this->option('name') === null ? null : trim($this->option('name'));

        $validator = Validator::make(
            ['email' => $email, 'name' => $name],
            ['email' => ['required', 'email', 'max:255'], 'name' => ['nullable', 'string', 'min:1', 'max:50']],
        );
        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $groups = Group::query()->findMany($this->option('group'));
        $missing = array_diff($this->option('group'), $groups->modelKeys());
        if ($missing !== []) {
            $this->components->error('No group with id '.implode(', ', $missing).'. See groups:list.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrCreate(['email' => $email]);
        $user->wasRecentlyCreated
            ? $this->components->info("Created account for {$email}.")
            : $this->components->warn("An account for {$email} already exists.");

        if ($name !== null && ! $user->profiles()->exists()) {
            $user->profiles()->create(['name' => $name, 'role' => ProfileRole::Admin]);
            $this->components->info("Added admin profile \"{$name}\".");
        }

        foreach ($groups as $group) {
            $group->addMember($user);
            $this->components->info("Added to {$group->name} (#{$group->id}).");
        }

        return self::SUCCESS;
    }
}
