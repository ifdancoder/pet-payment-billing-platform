<?php

namespace App\Presentation\Subscription\Adapters\Inbound\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid'],
            'price_id' => ['required', 'uuid'],
        ];
    }
}
