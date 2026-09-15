<?php

namespace App\Presentation\Price\Adapters\Inbound\Http\Requests;

use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreatePriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount_minor_units' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
            'billing_interval' => ['required', 'string', Rule::enum(BillingInterval::class)],
        ];
    }
}
