<?php

use App\Application\Subscription\Queries\GetSubscription\GetSubscriptionHandler;
use App\Application\Subscription\Queries\GetSubscription\GetSubscriptionQuery;
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
use App\Infrastructure\Subscription\Adapters\Persistence\Mappers\SubscriptionMapper;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

test('handle returns the matching subscription', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $merchantId = MerchantId::generate();
    $subscription = Subscription::create(
        SubscriptionId::generate(),
        $merchantId,
        CustomerId::generate(),
        PriceSnapshot::of(
            PriceId::generate(),
            ProductId::generate(),
            Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
    );
    $repository->save($subscription);
    $handler = new GetSubscriptionHandler($repository);

    $found = $handler->handle(new GetSubscriptionQuery($merchantId->toString(), $subscription->id()->toString()));

    expect($found->id()->equals($subscription->id()))->toBeTrue();
});

test('handle throws SubscriptionNotFound when no subscription matches', function () {
    $handler = new GetSubscriptionHandler(new EloquentSubscriptionRepository(new SubscriptionMapper));

    $handler->handle(new GetSubscriptionQuery(MerchantId::generate()->toString(), SubscriptionId::generate()->toString()));
})->throws(SubscriptionNotFound::class);

test('handle throws SubscriptionNotFound when the subscription belongs to a different merchant', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $subscription = Subscription::create(
        SubscriptionId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        PriceSnapshot::of(
            PriceId::generate(),
            ProductId::generate(),
            Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
    );
    $repository->save($subscription);
    $handler = new GetSubscriptionHandler($repository);

    $handler->handle(new GetSubscriptionQuery(MerchantId::generate()->toString(), $subscription->id()->toString()));
})->throws(SubscriptionNotFound::class);
