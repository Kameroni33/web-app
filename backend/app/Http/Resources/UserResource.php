<?php

namespace App\Http\Resources;

use App\Auth\ActiveProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * The signed-in account, plus the profile this device is using (null until
 * one is chosen).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            // What the app should offer; the server checks again on every request.
            'can' => [
                'manage_groups' => Gate::forUser($this->resource)->allows('manage-groups'),
            ],
            'profile' => $this->whenNotNull(
                ($profile = app(ActiveProfile::class)->get()) ? new ProfileResource($profile) : null,
            ),
        ];
    }
}
