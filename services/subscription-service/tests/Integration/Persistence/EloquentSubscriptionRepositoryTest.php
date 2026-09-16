<?php

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
use App\Infrastructure\Subscription\Adapters\Persistence\Models\SubscriptionModel;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Shared\Domain\ValueObjects\MerchantId;

function makeSubscription(?MerchantId $merchantId = null, ?CustomerId $customerId = null): Subscription
{
    return Subscription::create(
        SubscriptionId::generate(),
        $merchantId ?? MerchantId::generate(),
        $customerId ?? CustomerId::generate(),
        PriceSnapshot::of(
            PriceId::generate(),
            ProductId::generate(),
            Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
    );
}

test('save persists a new subscription', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $subscription = makeSubscription();

    $repository->save($subscription);

    expect(SubscriptionModel::query()->where('id', $subscription->id()->toString())->exists())->toBeTrue();
});

test('save updates an already-persisted subscription instead of duplicating it', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $subscription = makeSubscription();
    $repository->save($subscription);

    $subscription->activate();
    $repository->save($subscription);

    expect(SubscriptionModel::query()->where('id', $subscription->id()->toString())->count())->toBe(1)
        ->and(SubscriptionModel::query()->find($subscription->id()->toString())->status)->toBe(SubscriptionStatus::Active->value);
});

test('get returns the matching subscription for the owning merchant', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $merchantId = MerchantId::generate();
    $subscription = makeSubscription($merchantId);
    $repository->save($subscription);

    $found = $repository->get($subscription->id(), $merchantId);

    expect($found->id()->equals($subscription->id()))->toBeTrue()
        ->and($found->customerId()->equals($subscription->customerId()))->toBeTrue()
        ->and($found->priceSnapshot()->equals($subscription->priceSnapshot()))->toBeTrue()
        ->and($found->status())->toBe(SubscriptionStatus::Pending);
});

test('get throws SubscriptionNotFound when no subscription matches', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);

    $repository->get(SubscriptionId::generate(), MerchantId::generate());
})->throws(SubscriptionNotFound::class);

test('get throws SubscriptionNotFound when the subscription belongs to a different merchant', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $subscription = makeSubscription();
    $repository->save($subscription);

    $repository->get($subscription->id(), MerchantId::generate());
})->throws(SubscriptionNotFound::class);

test('all returns every persisted subscription for the given merchant', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);
    $merchantId = MerchantId::generate();
    $repository->save(makeSubscription($merchantId));
    $repository->save(makeSubscription($merchantId));
    $repository->save(makeSubscription());

    $subscriptions = $repository->all($merchantId);

    expect($subscriptions)->toHaveCount(2);
});

test('all returns an empty array when there are no subscriptions for the given merchant', function () {
    $repository = new EloquentSubscriptionRepository(new SubscriptionMapper);

    expect($repository->all(MerchantId::generate()))->toBe([]);
});
