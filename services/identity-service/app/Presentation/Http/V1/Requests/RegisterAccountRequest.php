<?php

namespace App\Presentation\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:255'],
            'merchant_name' => ['required', 'string', 'max:255'],
        ];
    }
}
