<?php

namespace App\Infrastructure\Price\Adapters\Persistence\Mappers;

use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;
use App\Shared\Domain\ValueObjects\MerchantId;

final class PriceMapper
{
    public function toDomain(PriceModel $model): Price
    {
        $billingPeriod = $model->billing_interval === null
            ? null
            : BillingPeriod::of(BillingInterval::from($model->billing_interval), $model->billing_interval_count);

        return Price::reconstitute(
            PriceId::fromString($model->id),
            MerchantId::fromString($model->merchant_id),
            ProductId::fromString($model->product_id),
            Money::of($model->amount_minor_units, Currency::from($model->currency)),
            PriceType::from($model->type),
            $billingPeriod,
            PriceStatus::from($model->status),
        );
    }

    public function toModel(Price $price, ?PriceModel $model = null): PriceModel
    {
        $model ??= new PriceModel;

        $model->id = $price->id()->toString();
        $model->merchant_id = $price->merchantId()->toString();
        $model->product_id = $price->productId()->toString();
        $model->amount_minor_units = $price->money()->amountMinorUnits();
        $model->currency = $price->money()->currency()->value;
        $model->type = $price->type()->value;
        $model->billing_interval = $price->billingPeriod()?->interval()->value;
        $model->billing_interval_count = $price->billingPeriod()?->count();
        $model->status = $price->status()->value;

        return $model;
    }
}
