<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGroupRequest;
use App\Http\Resources\Admin\AdminGroupResource;
use App\Models\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Every group, for admin accounts (routes use can:manage-groups).
 */
class GroupController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AdminGroupResource::collection(
            Group::query()->withCount('members')->orderBy('name')->get(),
        );
    }

    public function store(StoreGroupRequest $request): AdminGroupResource
    {
        return new AdminGroupResource(Group::query()->create($request->validated())->loadCount('members'));
    }

    public function show(Group $group): AdminGroupResource
    {
        $group->load([
            'members' => fn ($query) => $query->orderBy('email'),
            'members.profiles',
            'invites' => fn ($query) => $query->usable()->latest(),
        ])->loadCount('members');

        return new AdminGroupResource($group);
    }

    public function update(StoreGroupRequest $request, Group $group): AdminGroupResource
    {
        $group->update($request->validated());

        return new AdminGroupResource($group->loadCount('members'));
    }

    /**
     * Members' accounts are untouched; they just leave the family.
     */
    public function destroy(Group $group): Response
    {
        $group->delete();

        return response()->noContent();
    }
}
