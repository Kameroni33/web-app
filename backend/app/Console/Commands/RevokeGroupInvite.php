<?php

namespace App\Console\Commands;

use App\Groups\GroupInvites;
use App\Models\GroupInvite;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('groups:revoke-invite {invite : Invite id (see groups:invites)}')]
#[Description('Cancel an invite link so it can no longer be used')]
class RevokeGroupInvite extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GroupInvites $invites): int
    {
        $invite = GroupInvite::query()->find($this->argument('invite'));
        if ($invite === null) {
            $this->components->error('No invite with that id. See groups:invites --all.');

            return self::FAILURE;
        }

        $invites->revoke($invite);
        $this->components->info("Invite #{$invite->id} can no longer be used.");

        return self::SUCCESS;
    }
}
