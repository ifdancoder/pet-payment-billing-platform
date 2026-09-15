<?php

namespace App\Infrastructure\Price\Adapters\Persistence\Mappers;

use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Infrastructure\Price\Adapters\Persistence\Models\PriceModel;

final class PriceMapper
{
    public function toDomain(PriceModel $model): Price
    {
        return Price::reconstitute(
            PriceId::fromString($model->id),
            ProductId::fromString($model->product_id),
            Money::of($model->amount_minor_units, Currency::from($model->currency)),
            BillingInterval::from($model->billing_interval),
        );
    }

    public function toModel(Price $price, ?PriceModel $model = null): PriceModel
    {
        $model ??= new PriceModel;

        $model->id = $price->id()->toString();
        $model->product_id = $price->productId()->toString();
        $model->amount_minor_units = $price->money()->amountMinorUnits();
        $model->currency = $price->money()->currency()->value;
        $model->billing_interval = $price->billingInterval()->value;

        return $model;
    }
}
