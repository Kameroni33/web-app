<?php

namespace App\Console\Commands;

use App\Models\GroupInvite;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:invites {group? : Only this group id} {--all : Include expired, used-up and revoked invites}')]
#[Description('List invite links (the links themselves are never stored)')]
class ListGroupInvites extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $invites = GroupInvite::query()
            ->with(['group', 'creator'])
            ->when($this->argument('group'), fn ($query, $group) => $query->where('group_id', $group))
            ->unless($this->option('all'), fn ($query) => $query->usable())
            ->latest()
            ->get();

        $this->table(['Id', 'Group', 'Status', 'Uses', 'Expires', 'Created by'], $invites->map(fn (GroupInvite $invite): array => [
            $invite->id,
            $invite->group->name,
            $invite->status(),
            $invite->uses.' / '.($invite->max_uses ?? '∞'),
            $invite->expires_at->toDayDateTimeString(),
            $invite->creator?->email ?? 'command line',
        ]));

        return self::SUCCESS;
    }
}
