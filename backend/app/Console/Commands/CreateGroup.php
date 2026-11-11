<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:create {name : Shown in the app as the family name, e.g. "Robinson family"}')]
#[Description('Create a group (a "family" in the app)')]
class CreateGroup extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = trim($this->argument('name'));
        if ($name === '' || mb_strlen($name) > 100) {
            $this->components->error('The name must be 1 to 100 characters.');

            return self::FAILURE;
        }

        $group = Group::query()->create(['name' => $name]);
        $this->components->info("Created {$group->name} (#{$group->id}). Add accounts with: groups:add-member {$group->id} <email>");

        return self::SUCCESS;
    }
}
