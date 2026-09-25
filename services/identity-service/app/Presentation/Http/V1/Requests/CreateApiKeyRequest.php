<?php

namespace App\Presentation\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateApiKeyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'role' => ['sometimes', Rule::in(['admin', 'developer', 'finance', 'viewer'])],
            'scopes' => ['sometimes', 'array', 'max:32'],
            'scopes.*' => ['string', 'max:100', 'distinct'],
        ];
    }
}
