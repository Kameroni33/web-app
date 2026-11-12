<?php

namespace App\Enums;

/**
 * An account's role within a group (shown as a "family" in the app).
 *
 * Group management (creating groups, members, invites) belongs to admin
 * accounts, so no group role grants powers yet. Manager exists so
 * permissions can be scoped per group later.
 */
enum GroupRole: string
{
    case Manager = 'manager';

    case Member = 'member';
}
