<?php

use App\Application\Subscription\Commands\CancelSubscription\CancelSubscriptionCommand;
use App\Application\Subscription\Commands\CancelSubscription\CancelSubscriptionHandler;
use App\Domain\Subscription\Exceptions\InvalidSubscriptionTransition;
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

function seedSubscriptionForCancel(MerchantId $merchantId, SubscriptionStatus $status): Subscription
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

test('handle cancels an Active subscription', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForCancel($merchantId, SubscriptionStatus::Active);
    $handler = app(CancelSubscriptionHandler::class);

    $canceled = $handler->handle(new CancelSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    expect($canceled->status())->toBe(SubscriptionStatus::Canceled);
});

test('handle cancels a PastDue subscription', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForCancel($merchantId, SubscriptionStatus::PastDue);
    $handler = app(CancelSubscriptionHandler::class);

    $canceled = $handler->handle(new CancelSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    expect($canceled->status())->toBe(SubscriptionStatus::Canceled);
});

test('handle throws SubscriptionNotFound when no subscription matches', function () {
    app(CancelSubscriptionHandler::class)->handle(new CancelSubscriptionCommand(
        MerchantId::generate()->toString(),
        SubscriptionId::generate()->toString(),
    ));
})->throws(SubscriptionNotFound::class);

test('handle throws InvalidSubscriptionTransition when the subscription is Pending', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForCancel($merchantId, SubscriptionStatus::Pending);

    app(CancelSubscriptionHandler::class)->handle(new CancelSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));
})->throws(InvalidSubscriptionTransition::class);

test('handle throws InvalidSubscriptionTransition when the subscription is already Canceled', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForCancel($merchantId, SubscriptionStatus::Canceled);

    app(CancelSubscriptionHandler::class)->handle(new CancelSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));
})->throws(InvalidSubscriptionTransition::class);

test('handle records a SubscriptionCanceled integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForCancel($merchantId, SubscriptionStatus::Active);

    app(CancelSubscriptionHandler::class)->handle(new CancelSubscriptionCommand($merchantId->toString(), $subscription->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $canceled = collect($unpublished)->firstWhere('eventType', 'subscription.canceled.v1');
    expect($canceled)->not->toBeNull()
        ->and($canceled->aggregateId)->toBe($subscription->id()->toString());
});
