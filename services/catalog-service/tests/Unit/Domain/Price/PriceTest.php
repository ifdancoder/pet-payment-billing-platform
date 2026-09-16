<?php

use App\Domain\Price\Events\PriceActivated;
use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\Events\PriceDeactivated;
use App\Domain\Price\Exceptions\InvalidPrice;
use App\Domain\Price\Exceptions\PriceAlreadyActive;
use App\Domain\Price\Exceptions\PriceAlreadyInactive;
use App\Domain\Price\Price;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceStatus;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use App\Shared\Domain\ValueObjects\MerchantId;

test('create records a PriceCreated event and exposes the given data for a recurring price', function () {
    $id = PriceId::generate();
    $merchantId = MerchantId::generate();
    $productId = ProductId::generate();
    $money = Money::of(1999, Currency::USD);
    $period = BillingPeriod::of(BillingInterval::Month, 1);

    $price = Price::create($id, $merchantId, $productId, $money, PriceType::Recurring, $period);

    expect($price->id()->equals($id))->toBeTrue()
        ->and($price->merchantId()->equals($merchantId))->toBeTrue()
        ->and($price->productId()->equals($productId))->toBeTrue()
        ->and($price->money()->equals($money))->toBeTrue()
        ->and($price->type())->toBe(PriceType::Recurring)
        ->and($price->billingPeriod()->equals($period))->toBeTrue()
        ->and($price->status())->toBe(PriceStatus::Active);

    $events = $price->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PriceCreated::class)
        ->and($events[0]->priceId->equals($id))->toBeTrue()
        ->and($events[0]->productId->equals($productId))->toBeTrue()
        ->and($events[0]->money->equals($money))->toBeTrue()
        ->and($events[0]->type)->toBe(PriceType::Recurring)
        ->and($events[0]->billingPeriod?->equals($period))->toBeTrue();
});

test('create builds a one-time price without a billing period', function () {
    $price = Price::create(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(4999, Currency::USD),
        PriceType::OneTime,
    );

    expect($price->type())->toBe(PriceType::OneTime)
        ->and($price->billingPeriod())->toBeNull();
});

test('create throws when a recurring price has no billing period', function () {
    Price::create(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
    );
})->throws(InvalidPrice::class, 'A recurring price requires a billing period.');

test('create throws when a one-time price has a billing period', function () {
    Price::create(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(1999, Currency::USD),
        PriceType::OneTime,
        BillingPeriod::of(BillingInterval::Month, 1),
    );
})->throws(InvalidPrice::class, 'A one-time price must not have a billing period.');

test('pullRecordedEvents clears the recorded events', function () {
    $price = Price::create(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        PriceType::OneTime,
    );

    $price->pullRecordedEvents();

    expect($price->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given merchant without recording an event', function () {
    $merchantId = MerchantId::generate();

    $price = Price::reconstitute(
        PriceId::generate(),
        $merchantId,
        ProductId::generate(),
        Money::of(999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Week, 2),
        PriceStatus::Inactive,
    );

    expect($price->merchantId()->equals($merchantId))->toBeTrue()
        ->and($price->status())->toBe(PriceStatus::Inactive)
        ->and($price->pullRecordedEvents())->toBe([]);
});

test('deactivate sets the status to Inactive and records a PriceDeactivated event', function () {
    $price = Price::create(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        PriceType::OneTime,
    );
    $price->pullRecordedEvents();

    $price->deactivate();

    expect($price->status())->toBe(PriceStatus::Inactive);
    $events = $price->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PriceDeactivated::class)
        ->and($events[0]->priceId->equals($price->id()))->toBeTrue();
});

test('deactivate throws when the price is already inactive', function () {
    $price = Price::reconstitute(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        PriceType::OneTime,
        null,
        PriceStatus::Inactive,
    );

    $price->deactivate();
})->throws(PriceAlreadyInactive::class);

test('activate sets the status to Active and records a PriceActivated event', function () {
    $price = Price::reconstitute(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        PriceType::OneTime,
        null,
        PriceStatus::Inactive,
    );

    $price->activate();

    expect($price->status())->toBe(PriceStatus::Active);
    $events = $price->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(PriceActivated::class)
        ->and($events[0]->priceId->equals($price->id()))->toBeTrue();
});

test('activate throws when the price is already active', function () {
    $price = Price::create(
        PriceId::generate(),
        MerchantId::generate(),
        ProductId::generate(),
        Money::of(999, Currency::USD),
        PriceType::OneTime,
    );

    $price->activate();
})->throws(PriceAlreadyActive::class);
