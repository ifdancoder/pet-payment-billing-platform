<?php

namespace App\Infrastructure\Subscription\Adapters\Persistence\Mappers;

use App\Domain\Subscription\Subscription;
use App\Domain\Subscription\ValueObjects\BillingInterval;
use App\Domain\Subscription\ValueObjects\BillingPeriod;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\Money;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\ProductId;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Domain\Subscription\ValueObjects\SubscriptionStatus;
use App\Infrastructure\Subscription\Adapters\Persistence\Models\SubscriptionModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class SubscriptionMapper
{
    public function toDomain(SubscriptionModel $model): Subscription
    {
        return Subscription::reconstitute(
            SubscriptionId::fromString($model->id),
            MerchantId::fromString($model->merchant_id),
            CustomerId::fromString($model->customer_id),
            PriceSnapshot::of(
                PriceId::fromString($model->price_id),
                ProductId::fromString($model->product_id),
                Money::of($model->price_amount_minor_units, Currency::from($model->price_currency)),
                BillingPeriod::of(BillingInterval::from($model->billing_interval), $model->billing_interval_count),
            ),
            SubscriptionStatus::from($model->status),
        );
    }

    public function toModel(Subscription $subscription, ?SubscriptionModel $model = null): SubscriptionModel
    {
        $model ??= new SubscriptionModel;

        $priceSnapshot = $subscription->priceSnapshot();

        $model->id = $subscription->id()->toString();
        $model->merchant_id = $subscription->merchantId()->toString();
        $model->customer_id = $subscription->customerId()->toString();
        $model->price_id = $priceSnapshot->priceId()->toString();
        $model->product_id = $priceSnapshot->productId()->toString();
        $model->price_amount_minor_units = $priceSnapshot->money()->amountMinorUnits();
        $model->price_currency = $priceSnapshot->money()->currency()->value;
        $model->billing_interval = $priceSnapshot->billingPeriod()->interval()->value;
        $model->billing_interval_count = $priceSnapshot->billingPeriod()->count();
        $model->status = $subscription->status()->value;

        return $model;
    }
}
