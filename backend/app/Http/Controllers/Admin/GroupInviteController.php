<?php

namespace App\Http\Controllers\Admin;

use App\Groups\GroupInvites;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGroupInviteRequest;
use App\Http\Resources\GroupInviteResource;
use App\Models\Group;
use App\Models\GroupInvite;
use Illuminate\Http\Response;

class GroupInviteController extends Controller
{
    public function __construct(private GroupInvites $invites) {}

    /**
     * The response is the only time the link is shown.
     */
    public function store(StoreGroupInviteRequest $request, Group $group): GroupInviteResource
    {
        [$invite, $url] = $this->invites->create(
            $group,
            $request->user(),
            $request->validated('max_uses'),
            $request->integer('expires_in_days'),
        );

        return (new GroupInviteResource($invite->refresh()))->additional(['url' => $url]);
    }

    public function destroy(GroupInvite $invite): Response
    {
        $this->invites->revoke($invite);

        return response()->noContent();
    }
}
