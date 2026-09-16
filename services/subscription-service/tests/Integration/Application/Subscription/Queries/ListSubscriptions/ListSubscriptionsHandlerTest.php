<?php

use App\Application\Subscription\Queries\ListSubscriptions\ListSubscriptionsHandler;
use App\Application\Subscription\Queries\ListSubscriptions\ListSubscriptionsQuery;
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

function aSubscriptionFor(MerchantId $merchantId): Subscription
{
    return Subscription::create(
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
}

test('handle returns every persisted subscription for the given merchant', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $merchantId = MerchantId::generate();
    $repository->save(aSubscriptionFor($merchantId));
    $repository->save(aSubscriptionFor($merchantId));
    $repository->save(aSubscriptionFor(MerchantId::generate()));
    $handler = new ListSubscriptionsHandler($repository);

    $subscriptions = $handler->handle(new ListSubscriptionsQuery($merchantId->toString()));

    expect($subscriptions)->toHaveCount(2);
});

test('handle returns an empty array when there are no subscriptions for the given merchant', function () {
    $handler = new ListSubscriptionsHandler(new EloquentSubscriptionRepository(new SubscriptionMapper));

    expect($handler->handle(new ListSubscriptionsQuery(MerchantId::generate()->toString())))->toBe([]);
});
