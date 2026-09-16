<?php

use App\Domain\Subscription\ValueObjects\BillingInterval;
use App\Domain\Subscription\ValueObjects\BillingPeriod;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\Money;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\ProductId;

test('it exposes the price id, product id, money and billing period it was captured with', function () {
    $priceId = PriceId::generate();
    $productId = ProductId::generate();
    $money = Money::of(1999, Currency::USD);
    $period = BillingPeriod::of(BillingInterval::Month, 1);

    $snapshot = PriceSnapshot::of($priceId, $productId, $money, $period);

    expect($snapshot->priceId()->equals($priceId))->toBeTrue()
        ->and($snapshot->productId()->equals($productId))->toBeTrue()
        ->and($snapshot->money()->equals($money))->toBeTrue()
        ->and($snapshot->billingPeriod()->equals($period))->toBeTrue();
});

test('two snapshots with the same data are equal', function () {
    $priceId = PriceId::generate();
    $productId = ProductId::generate();
    $money = Money::of(1999, Currency::USD);
    $period = BillingPeriod::of(BillingInterval::Month, 1);

    $a = PriceSnapshot::of($priceId, $productId, $money, $period);
    $b = PriceSnapshot::of($priceId, $productId, $money, $period);

    expect($a->equals($b))->toBeTrue();
});

test('two snapshots with different data are not equal', function () {
    $priceId = PriceId::generate();
    $productId = ProductId::generate();
    $money = Money::of(1999, Currency::USD);
    $period = BillingPeriod::of(BillingInterval::Month, 1);

    $a = PriceSnapshot::of($priceId, $productId, $money, $period);
    $b = PriceSnapshot::of($priceId, $productId, Money::of(2999, Currency::USD), $period);

    expect($a->equals($b))->toBeFalse();
});
