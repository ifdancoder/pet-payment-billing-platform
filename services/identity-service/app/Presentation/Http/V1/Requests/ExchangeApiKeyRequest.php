<?php

namespace App\Presentation\Http\V1\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExchangeApiKeyRequest extends FormRequest
{
    public function rules(): array { return ['api_key' => ['required', 'string', 'max:256']]; }
}
