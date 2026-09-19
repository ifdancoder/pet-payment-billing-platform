<?php

use App\Application\Subscription\Commands\HandleInvoicePaymentFailed\HandleInvoicePaymentFailedCommand;
use App\Application\Subscription\Commands\HandleInvoicePaymentFailed\HandleInvoicePaymentFailedHandler;
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

function seedSubscriptionForInvoicePaymentFailed(MerchantId $merchantId, SubscriptionStatus $status): Subscription
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
    $subscription = seedSubscriptionForInvoicePaymentFailed($merchantId, SubscriptionStatus::Active);

    $marked = app(HandleInvoicePaymentFailedHandler::class)->handle(new HandleInvoicePaymentFailedCommand(
        (string) Str::uuid(),
        'invoice.payment_failed.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    expect($marked->status())->toBe(SubscriptionStatus::PastDue);
});

test('handle leaves a Pending subscription Pending (first payment failed before ever activating)', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaymentFailed($merchantId, SubscriptionStatus::Pending);

    $result = app(HandleInvoicePaymentFailedHandler::class)->handle(new HandleInvoicePaymentFailedCommand(
        (string) Str::uuid(),
        'invoice.payment_failed.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    expect($result->status())->toBe(SubscriptionStatus::Pending);
});

test('handle is idempotent when the subscription is already PastDue', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaymentFailed($merchantId, SubscriptionStatus::PastDue);

    $result = app(HandleInvoicePaymentFailedHandler::class)->handle(new HandleInvoicePaymentFailedCommand(
        (string) Str::uuid(),
        'invoice.payment_failed.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    expect($result->status())->toBe(SubscriptionStatus::PastDue);
});

test('handle throws SubscriptionNotFound when no subscription matches', function () {
    app(HandleInvoicePaymentFailedHandler::class)->handle(new HandleInvoicePaymentFailedCommand(
        (string) Str::uuid(),
        'invoice.payment_failed.v1',
        SubscriptionId::generate()->toString(),
        MerchantId::generate()->toString(),
    ));
})->throws(SubscriptionNotFound::class);

test('handle does nothing when the same event id is redelivered', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaymentFailed($merchantId, SubscriptionStatus::Active);
    $command = new HandleInvoicePaymentFailedCommand((string) Str::uuid(), 'invoice.payment_failed.v1', $subscription->id()->toString(), $merchantId->toString());
    app(HandleInvoicePaymentFailedHandler::class)->handle($command);

    $result = app(HandleInvoicePaymentFailedHandler::class)->handle($command);

    expect($result)->toBeNull();
});

test('handle persists the PastDue status', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaymentFailed($merchantId, SubscriptionStatus::Active);

    app(HandleInvoicePaymentFailedHandler::class)->handle(new HandleInvoicePaymentFailedCommand(
        (string) Str::uuid(),
        'invoice.payment_failed.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    $persisted = app(ISubscriptionRepositoryPort::class)->get($subscription->id(), $merchantId);
    expect($persisted->status())->toBe(SubscriptionStatus::PastDue);
});

test('handle records a SubscriptionMarkedPastDue integration event in the outbox', function () {
    $merchantId = MerchantId::generate();
    $subscription = seedSubscriptionForInvoicePaymentFailed($merchantId, SubscriptionStatus::Active);

    app(HandleInvoicePaymentFailedHandler::class)->handle(new HandleInvoicePaymentFailedCommand(
        (string) Str::uuid(),
        'invoice.payment_failed.v1',
        $subscription->id()->toString(),
        $merchantId->toString(),
    ));

    $marked = collect(app(IOutboxPort::class)->unpublished())->firstWhere('eventType', 'subscription.past_due.v1');
    expect($marked)->not->toBeNull();
});
