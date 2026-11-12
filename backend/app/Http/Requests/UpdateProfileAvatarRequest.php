<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileAvatarRequest extends FormRequest
{
    /**
     * Authorization happens in the controller (ProfilePolicy).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The app shrinks photos before sending; the cap is for anything else.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.mimes' => 'Choose a JPEG, PNG, WebP or GIF photo.',
            'image.max' => 'That photo is too big. Choose one under 5 MB.',
            'image.dimensions' => 'That photo is too big. Choose a smaller one.',
        ];
    }
}
