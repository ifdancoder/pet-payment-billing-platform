<?php

namespace App\Presentation\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddMembershipRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,id'],
            'role' => ['required', Rule::in(['owner', 'admin', 'developer', 'finance', 'viewer'])],
        ];
    }
}
