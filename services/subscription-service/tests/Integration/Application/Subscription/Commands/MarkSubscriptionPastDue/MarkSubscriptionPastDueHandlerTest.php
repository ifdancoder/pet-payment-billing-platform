<?php

use App\Application\Subscription\Commands\MarkSubscriptionPastDue\MarkSubscriptionPastDueCommand;
use App\Application\Subscription\Commands\MarkSubscriptionPastDue\MarkSubscriptionPastDueHandler;
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

function seedSubscriptionForPastDue(MerchantId $merchantId, SubscriptionStatus $status): Subscription
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

test('handle marks an Active subscription PastDue', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForPastDue($merchantId, SubscriptionStatus::Active);
    $handler = app(MarkSubscriptionPastDueHandler::class);

    $marked = $handler->handle(new MarkSubscriptionPastDueCommand($merchantId->toString(), $subscription->id()->toString()));

    expect($marked->status())->toBe(SubscriptionStatus::PastDue);
});

test('handle throws SubscriptionNotFound when no subscription matches', function () {
    app(MarkSubscriptionPastDueHandler::class)->handle(new MarkSubscriptionPastDueCommand(
        MerchantId::generate()->toString(),
        SubscriptionId::generate()->toString(),
    ));
})->throws(SubscriptionNotFound::class);

test('handle throws InvalidSubscriptionTransition when the subscription is Pending', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForPastDue($merchantId, SubscriptionStatus::Pending);

    app(MarkSubscriptionPastDueHandler::class)->handle(new MarkSubscriptionPastDueCommand($merchantId->toString(), $subscription->id()->toString()));
})->throws(InvalidSubscriptionTransition::class);

test('handle records a SubscriptionMarkedPastDue integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForPastDue($merchantId, SubscriptionStatus::Active);

    app(MarkSubscriptionPastDueHandler::class)->handle(new MarkSubscriptionPastDueCommand($merchantId->toString(), $subscription->id()->toString()));

    $unpublished = app(IOutboxPort::class)->unpublished();
    $marked = collect($unpublished)->firstWhere('eventType', 'subscription.past_due.v1');
    expect($marked)->not->toBeNull()
        ->and($marked->aggregateId)->toBe($subscription->id()->toString());
});
