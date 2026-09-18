<?php

namespace App\Presentation\Http\V1\Requests;

use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\PriceType;
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
            'type' => ['required', 'integer', Rule::enum(PriceType::class)],
            'billing_interval' => [
                Rule::requiredIf($this->integer('type') === PriceType::Recurring->value),
                Rule::prohibitedIf($this->integer('type') === PriceType::OneTime->value),
                'integer',
                Rule::enum(BillingInterval::class),
            ],
            'billing_interval_count' => [
                Rule::requiredIf($this->integer('type') === PriceType::Recurring->value),
                Rule::prohibitedIf($this->integer('type') === PriceType::OneTime->value),
                'integer',
                'min:1',
            ],
        ];
    }
}
