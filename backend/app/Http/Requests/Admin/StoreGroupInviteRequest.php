<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Access is checked by the route's can:manage-groups.
 */
class StoreGroupInviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'max_uses' => ['present', 'nullable', 'integer', 'min:1', 'max:100'],
            'expires_in_days' => ['required', 'integer', 'min:1', 'max:90'],
        ];
    }
}
