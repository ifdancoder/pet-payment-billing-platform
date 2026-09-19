<?php

use App\Application\Subscription\Commands\ActivateSubscription\ActivateSubscriptionCommand;
use App\Application\Subscription\Commands\ActivateSubscription\ActivateSubscriptionHandler;
use App\Domain\Subscription\Exceptions\SubscriptionNotFound;
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
use App\Infrastructure\Subscription\Adapters\Persistence\Mappers\SubscriptionMapper;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Shared\Application\Ports\Outbound\IOutboxPort;
use App\Shared\Domain\ValueObjects\MerchantId;

function seedSubscription(MerchantId $merchantId, SubscriptionStatus $status): Subscription
{
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $subscription = Subscription::reconstitute(
        SubscriptionId::generate(),
        $merchantId,
        CustomerId::generate(),
        PriceSnapshot::of(
            PriceId::generate(),
            ProductId::generate(),
            Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
        $status,
    );
    $repository->save($subscription);

    return $subscription;
}

test('handle activates a Pending subscription', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscription($merchantId, SubscriptionStatus::Pending);
    $handler = app(ActivateSubscriptionHandler::class);

    $activated = $handler->handle(new ActivateSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    expect($activated->status())->toBe(SubscriptionStatus::Active);
});

test('handle activates a PastDue subscription', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscription($merchantId, SubscriptionStatus::PastDue);
    $handler = app(ActivateSubscriptionHandler::class);

    $activated = $handler->handle(new ActivateSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    expect($activated->status())->toBe(SubscriptionStatus::Active);
});

test('handle throws SubscriptionNotFound when no subscription matches', function () {
    app(ActivateSubscriptionHandler::class)->handle(new ActivateSubscriptionCommand(
        MerchantId::generate()->toString(),
        SubscriptionId::generate()->toString(),
    ));
})->throws(SubscriptionNotFound::class);

test('handle is idempotent when the subscription is already Active', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscription($merchantId, SubscriptionStatus::Active);

    $result = app(ActivateSubscriptionHandler::class)->handle(new ActivateSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    expect($result->status())->toBe(SubscriptionStatus::Active);
});

test('handle records a SubscriptionActivated integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscription($merchantId, SubscriptionStatus::Pending);

    app(ActivateSubscriptionHandler::class)->handle(new ActivateSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $activated = collect($unpublished)->firstWhere('eventType', 'subscription.activated.v1');
    expect($activated)->not->toBeNull()
        ->and($activated->aggregateId)->toBe($subscription->id()->toString());
});
