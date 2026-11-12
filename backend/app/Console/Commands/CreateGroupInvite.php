<?php

namespace App\Console\Commands;

use App\Groups\GroupInvites;
use App\Models\Group;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:invite
    {group : Group id}
    {--uses=1 : How many people can use the link (0 = unlimited)}
    {--days=7 : Days until the link expires (1-90)}')]
#[Description('Create an invite link to join a group')]
class CreateGroupInvite extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GroupInvites $invites): int
    {
        $group = Group::query()->find($this->argument('group'));
        $uses = filter_var($this->option('uses'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]);
        $days = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 90]]);

        if ($group === null || $uses === false || $days === false) {
            $this->components->error($group === null ? 'No group with that id. See groups:list.' : '--uses must be 0-100 and --days 1-90.');

            return self::FAILURE;
        }

        [$invite, $url] = $invites->create($group, null, $uses === 0 ? null : $uses, $days);

        $this->components->info(sprintf(
            'Invite #%d to %s: %s, expires %s.',
            $invite->id,
            $group->name,
            $uses === 0 ? 'unlimited uses' : ($uses === 1 ? 'one use' : "{$uses} uses"),
            $invite->expires_at->toDayDateTimeString(),
        ));
        $this->line($url);
        $this->newLine();
        $this->components->warn('This is the only time the link is shown.');

        return self::SUCCESS;
    }
}
