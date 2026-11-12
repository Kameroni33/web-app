<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Response;

/**
 * Members join through invite links; admins can remove them.
 */
class GroupMemberController extends Controller
{
    public function destroy(Group $group, User $member): Response
    {
        abort_if($group->members()->detach($member) === 0, 404);

        return response()->noContent();
    }
}
