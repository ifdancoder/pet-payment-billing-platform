<?php

namespace App\Presentation\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['refresh_token' => ['required', 'string', 'starts_with:rt_', 'max:255']];
    }
}
