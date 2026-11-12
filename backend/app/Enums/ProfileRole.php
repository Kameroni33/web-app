<?php

namespace App\Enums;

/**
 * A profile's role within its own account.
 */
enum ProfileRole: string
{
    /** Manages the account's profiles. Every account keeps at least one. */
    case Admin = 'admin';

    case Member = 'member';
}
