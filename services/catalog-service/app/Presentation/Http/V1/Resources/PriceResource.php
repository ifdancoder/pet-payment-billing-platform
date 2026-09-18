<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Price\Price;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PriceResource extends JsonResource
{
    public function __construct(private readonly Price $price)
    {
        parent::__construct($price);
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->price->id()->toString(),
            'merchant_id' => $this->price->merchantId()->toString(),
            'product_id' => $this->price->productId()->toString(),
            'amount_minor_units' => $this->price->money()->amountMinorUnits(),
            'currency' => $this->price->money()->currency()->value,
            'type' => $this->price->type()->label(),
            'billing_interval' => $this->price->billingPeriod()?->interval()->label(),
            'billing_interval_count' => $this->price->billingPeriod()?->count(),
            'status' => $this->price->status()->label(),
        ];
    }
}
