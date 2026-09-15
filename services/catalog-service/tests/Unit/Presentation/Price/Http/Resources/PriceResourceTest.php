<?php

use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use App\Presentation\Price\Adapters\Inbound\Http\Resources\PriceResource;
use Illuminate\Http\Request;

test('toArray exposes the fields of a recurring price', function () {
    $id = PriceId::generate();
    $productId = ProductId::generate();
    $price = Price::create(
        $id,
        $productId,
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    );

    $array = (new PriceResource($price))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'product_id' => $productId->toString(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'type' => 'recurring',
        'billing_interval' => 'month',
        'billing_interval_count' => 1,
    ]);
});

test('toArray exposes null billing period fields for a one-time price', function () {
    $id = PriceId::generate();
    $productId = ProductId::generate();
    $price = Price::create($id, $productId, Money::of(4999, Currency::USD), PriceType::OneTime);

    $array = (new PriceResource($price))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'product_id' => $productId->toString(),
        'amount_minor_units' => 4999,
        'currency' => 'USD',
        'type' => 'one_time',
        'billing_interval' => null,
        'billing_interval_count' => null,
    ]);
});

test('constructing with a non-Price value fails with a TypeError', function () {
    new PriceResource('not-a-price');
})->throws(TypeError::class);
