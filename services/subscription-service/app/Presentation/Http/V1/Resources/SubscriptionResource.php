<?php

namespace App\Presentation\Http\V1\Resources;

use App\Domain\Subscription\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SubscriptionResource extends JsonResource
{
    public function __construct(private readonly Subscription $subscription)
    {
        parent::__construct($subscription);
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toArray(Request $request): array
    {
        $priceSnapshot = $this->subscription->priceSnapshot();

        return [
            'id' => $this->subscription->id()->toString(),
            'merchant_id' => $this->subscription->merchantId()->toString(),
            'customer_id' => $this->subscription->customerId()->toString(),
            'price_id' => $priceSnapshot->priceId()->toString(),
            'product_id' => $priceSnapshot->productId()->toString(),
            'amount_minor_units' => $priceSnapshot->money()->amountMinorUnits(),
            'currency' => $priceSnapshot->money()->currency()->value,
            'billing_interval' => $priceSnapshot->billingPeriod()->interval()->label(),
            'billing_interval_count' => $priceSnapshot->billingPeriod()->count(),
            'status' => $this->subscription->status()->label(),
            'current_period_start' => $this->subscription->currentPeriodStart()->format(DATE_ATOM),
            'current_period_end' => $this->subscription->currentPeriodEnd()->format(DATE_ATOM),
        ];
    }
}
