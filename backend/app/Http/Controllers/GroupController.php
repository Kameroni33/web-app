<?php

namespace App\Http\Controllers;

use App\Http\Resources\GroupResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The families the signed-in account belongs to. Read-only: groups are
 * managed from the command line (groups:* commands).
 */
class GroupController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $groups = $request->user()->groups()
            ->with('members.profiles')
            ->orderBy('name')
            ->get();

        return GroupResource::collection($groups);
    }
}
