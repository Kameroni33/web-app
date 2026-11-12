<?php

namespace App\Console\Commands;

use App\Models\Group;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:delete {group : Group id} {--force : Skip the confirmation}')]
#[Description('Delete a group and its invites (member accounts are kept)')]
class DeleteGroup extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $group = Group::query()->withCount('members')->find($this->argument('group'));
        if ($group === null) {
            $this->components->error('No group with that id. See groups:list.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$group->name} ({$group->members_count} members)?")) {
            return self::FAILURE;
        }

        $group->delete();
        $this->components->info("Deleted {$group->name}.");

        return self::SUCCESS;
    }
}
