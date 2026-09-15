<?php

use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;

test('create records a PriceCreated event and exposes the given data', function () {
    $id = PriceId::generate();
    $productId = ProductId::generate();
    $money = Money::of(1999, Currency::USD);
    $interval = BillingInterval::Monthly;

    $price = Price::create($id, $productId, $money, $interval);

    expect($price->id()->equals($id))->toBeTrue()
        ->and($price->productId()->equals($productId))->toBeTrue()
        ->and($price->money()->equals($money))->toBeTrue()
        ->and($price->billingInterval())->toBe($interval);

    $events = $price->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PriceCreated::class)
        ->and($events[0]->priceId->equals($id))->toBeTrue()
        ->and($events[0]->productId->equals($productId))->toBeTrue()
        ->and($events[0]->money->equals($money))->toBeTrue()
        ->and($events[0]->billingInterval)->toBe($interval);
});

test('pullRecordedEvents clears the recorded events', function () {
    $price = Price::create(
        PriceId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        BillingInterval::Yearly,
    );

    $price->pullRecordedEvents();

    expect($price->pullRecordedEvents())->toBe([]);
});

test('reconstitute does not record any event', function () {
    $price = Price::reconstitute(
        PriceId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        BillingInterval::Weekly,
    );

    expect($price->pullRecordedEvents())->toBe([]);
});
