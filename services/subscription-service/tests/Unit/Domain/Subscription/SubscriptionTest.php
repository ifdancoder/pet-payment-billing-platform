<?php

use App\Domain\Subscription\Events\SubscriptionActivated;
use App\Domain\Subscription\Events\SubscriptionCanceled;
use App\Domain\Subscription\Events\SubscriptionCreated;
use App\Domain\Subscription\Events\SubscriptionMarkedPastDue;
use App\Domain\Subscription\Exceptions\InvalidSubscriptionTransition;
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
use App\Shared\Domain\ValueObjects\MerchantId;

function makePriceSnapshot(): PriceSnapshot
{
    return PriceSnapshot::of(
        PriceId::generate(),
        ProductId::generate(),
        Money::of(1999, Currency::USD),
        BillingPeriod::of(BillingInterval::Month, 1),
    );
}

test('create exposes the given data and starts out Pending', function () {
    $id = SubscriptionId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceSnapshot = makePriceSnapshot();

    $subscription = Subscription::create($id, $merchantId, $customerId, $priceSnapshot);

    expect($subscription->id()->equals($id))->toBeTrue()
        ->and($subscription->merchantId()->equals($merchantId))->toBeTrue()
        ->and($subscription->customerId()->equals($customerId))->toBeTrue()
        ->and($subscription->priceSnapshot()->equals($priceSnapshot))->toBeTrue()
        ->and($subscription->status())->toBe(SubscriptionStatus::Pending);
});

test('create records a SubscriptionCreated event carrying the same data', function () {
    $id = SubscriptionId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceSnapshot = makePriceSnapshot();

    $subscription = Subscription::create($id, $merchantId, $customerId, $priceSnapshot);
    $events = $subscription->pullRecordedEvents();

    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(SubscriptionCreated::class)
        ->and($events[0]->subscriptionId->equals($id))->toBeTrue()
        ->and($events[0]->merchantId->equals($merchantId))->toBeTrue()
        ->and($events[0]->customerId->equals($customerId))->toBeTrue()
        ->and($events[0]->priceSnapshot->equals($priceSnapshot))->toBeTrue();
});

test('pullRecordedEvents empties the recorded events', function () {
    $subscription = Subscription::create(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot());

    $subscription->pullRecordedEvents();

    expect($subscription->pullRecordedEvents())->toBe([]);
});

test('reconstitute exposes the given data and status without recording an event', function () {
    $id = SubscriptionId::generate();
    $merchantId = MerchantId::generate();

    $subscription = Subscription::reconstitute($id, $merchantId, CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::Active);

    expect($subscription->id()->equals($id))->toBeTrue()
        ->and($subscription->merchantId()->equals($merchantId))->toBeTrue()
        ->and($subscription->status())->toBe(SubscriptionStatus::Active)
        ->and($subscription->pullRecordedEvents())->toBe([]);
});

test('activate sets the status to Active and records a SubscriptionActivated event when Pending', function () {
    $subscription = Subscription::create(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot());
    $subscription->pullRecordedEvents();

    $subscription->activate();

    expect($subscription->status())->toBe(SubscriptionStatus::Active);
    $events = $subscription->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(SubscriptionActivated::class)
        ->and($events[0]->subscriptionId->equals($subscription->id()))->toBeTrue();
});

test('activate sets the status to Active when PastDue', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::PastDue);

    $subscription->activate();

    expect($subscription->status())->toBe(SubscriptionStatus::Active);
});

test('activate throws when the subscription is already Active', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::Active);

    $subscription->activate();
})->throws(InvalidSubscriptionTransition::class);

test('activate throws when the subscription is Canceled', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::Canceled);

    $subscription->activate();
})->throws(InvalidSubscriptionTransition::class);

test('markPastDue sets the status to PastDue and records a SubscriptionMarkedPastDue event when Active', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::Active);

    $subscription->markPastDue();

    expect($subscription->status())->toBe(SubscriptionStatus::PastDue);
    $events = $subscription->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(SubscriptionMarkedPastDue::class)
        ->and($events[0]->subscriptionId->equals($subscription->id()))->toBeTrue();
});

test('markPastDue throws when the subscription is Pending', function () {
    $subscription = Subscription::create(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot());

    $subscription->markPastDue();
})->throws(InvalidSubscriptionTransition::class);

test('markPastDue throws when the subscription is already PastDue', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::PastDue);

    $subscription->markPastDue();
})->throws(InvalidSubscriptionTransition::class);

test('cancel sets the status to Canceled and records a SubscriptionCanceled event when Active', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::Active);

    $subscription->cancel();

    expect($subscription->status())->toBe(SubscriptionStatus::Canceled);
    $events = $subscription->pullRecordedEvents();
    expect($events)->toHaveCount(1)
        ->and($events[0])->toBeInstanceOf(SubscriptionCanceled::class)
        ->and($events[0]->subscriptionId->equals($subscription->id()))->toBeTrue();
});

test('cancel sets the status to Canceled when PastDue', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::PastDue);

    $subscription->cancel();

    expect($subscription->status())->toBe(SubscriptionStatus::Canceled);
});

test('cancel throws when the subscription is Pending', function () {
    $subscription = Subscription::create(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot());

    $subscription->cancel();
})->throws(InvalidSubscriptionTransition::class);

test('cancel throws when the subscription is already Canceled', function () {
    $subscription = Subscription::reconstitute(SubscriptionId::generate(), MerchantId::generate(), CustomerId::generate(), makePriceSnapshot(), SubscriptionStatus::Canceled);

    $subscription->cancel();
})->throws(InvalidSubscriptionTransition::class);
