<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:rename {group : Group id} {name : New name}')]
#[Description('Rename a group')]
class RenameGroup extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $group = Group::query()->find($this->argument('group'));
        $name = trim($this->argument('name'));

        if ($group === null || $name === '' || mb_strlen($name) > 100) {
            $this->components->error($group === null ? 'No group with that id. See groups:list.' : 'The name must be 1 to 100 characters.');

            return self::FAILURE;
        }

        $group->update(['name' => $name]);
        $this->components->info("Renamed #{$group->id} to {$name}.");

        return self::SUCCESS;
    }
}
