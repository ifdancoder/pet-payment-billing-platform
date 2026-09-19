<?php

use App\Application\Subscription\Commands\HandleInvoicePaid\HandleInvoicePaidCommand;
use App\Application\Subscription\Commands\HandleInvoicePaid\HandleInvoicePaidHandler;
use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
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
use Illuminate\Support\Str;

function seedSubscriptionForInvoicePaid(MerchantId $merchantId, SubscriptionStatus $status): Subscription
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
    $subscription = seedSubscriptionForInvoicePaid($merchantId, SubscriptionStatus::Pending);

    $activated = app(HandleInvoicePaidHandler::class)->handle(new HandleInvoicePaidCommand(
        (string) Str::uuid(),
        'invoice.paid.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    expect($activated->status())->toBe(SubscriptionStatus::Active);
});

test('handle is idempotent when the subscription is already Active (a renewal payment)', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaid($merchantId, SubscriptionStatus::Active);

    $result = app(HandleInvoicePaidHandler::class)->handle(new HandleInvoicePaidCommand(
        (string) Str::uuid(),
        'invoice.paid.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    expect($result->status())->toBe(SubscriptionStatus::Active);
});

test('handle leaves a Canceled subscription Canceled', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaid($merchantId, SubscriptionStatus::Canceled);

    $result = app(HandleInvoicePaidHandler::class)->handle(new HandleInvoicePaidCommand(
        (string) Str::uuid(),
        'invoice.paid.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    expect($result->status())->toBe(SubscriptionStatus::Canceled);
});

test('handle throws SubscriptionNotFound when no subscription matches', function () {
    app(HandleInvoicePaidHandler::class)->handle(new HandleInvoicePaidCommand(
        (string) Str::uuid(),
        'invoice.paid.v1',
        SubscriptionId::generate()->toString(),
        MerchantId::generate()->toString(),
    ));
})->throws(SubscriptionNotFound::class);

test('handle does nothing when the same event id is redelivered', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaid($merchantId, SubscriptionStatus::Pending);
    $command = new HandleInvoicePaidCommand((string) Str::uuid(), 'invoice.paid.v1', $subscription->id()->toString(), $merchantId->toString());
    app(HandleInvoicePaidHandler::class)->handle($command);

    $result = app(HandleInvoicePaidHandler::class)->handle($command);

    expect($result)->toBeNull();
});

test('handle persists the Active status', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaid($merchantId, SubscriptionStatus::Pending);

    app(HandleInvoicePaidHandler::class)->handle(new HandleInvoicePaidCommand(
        (string) Str::uuid(),
        'invoice.paid.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    $persisted = app(ISubscriptionRepositoryPort::class)->get($subscription->id(), $merchantId);
    expect($persisted->status())->toBe(SubscriptionStatus::Active);
});

test('handle records a SubscriptionActivated integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaid($merchantId, SubscriptionStatus::Pending);

    app(HandleInvoicePaidHandler::class)->handle(new HandleInvoicePaidCommand(
        (string) Str::uuid(),
        'invoice.paid.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    $activated = collect(app(IOutboxPort::class)->unpublished())->firstWhere('eventType', 'subscription.activated.v1');
    expect($activated)->not->toBeNull();
});
