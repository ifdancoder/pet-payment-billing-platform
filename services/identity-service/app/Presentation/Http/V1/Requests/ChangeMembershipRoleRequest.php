<?php

namespace App\Presentation\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeMembershipRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return ['role' => ['required', Rule::in(['owner', 'admin', 'developer', 'finance', 'viewer'])]];
    }
}
