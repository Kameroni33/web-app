<?php

namespace App\Http\Requests\Gifts;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Adding or editing one of your own items (ownership is checked in the
 * controller). On update every field is optional.
 */
class StoreGiftItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => [$required, 'string', 'max:200'],
            'links' => ['sometimes', 'array', 'max:5'],
            'links.*' => ['string', 'url:http,https', 'max:2000'],
            'category' => ['sometimes', 'nullable', 'string', 'max:50'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'received' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['links.*.url' => 'Links must start with http:// or https://.'];
    }
}
