<?php

use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Presentation\Price\Adapters\Inbound\Http\Resources\PriceResource;
use Illuminate\Http\Request;

test('toArray exposes the price fields', function () {
    $id = PriceId::generate();
    $productId = ProductId::generate();
    $price = Price::create($id, $productId, Money::of(1999, Currency::USD), BillingInterval::Monthly);

    $array = (new PriceResource($price))->toArray(new Request);

    expect($array)->toBe([
        'id' => $id->toString(),
        'product_id' => $productId->toString(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'billing_interval' => 'monthly',
    ]);
});

test('constructing with a non-Price value fails with a TypeError', function () {
    new PriceResource('not-a-price');
})->throws(TypeError::class);
