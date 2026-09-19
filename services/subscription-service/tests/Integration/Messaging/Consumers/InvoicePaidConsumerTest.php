<?php

use App\Application\Subscription\Ports\Outbound\ISubscriptionRepositoryPort;
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
use App\Infrastructure\Subscription\Adapters\Messaging\Consumers\InvoicePaidConsumer;
use App\Infrastructure\Subscription\Adapters\Persistence\Mappers\SubscriptionMapper;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

test('handle activates the subscription', function () {
    $merchantId = MerchantId::generate();
    $subscription = Subscription::reconstitute(
        SubscriptionId::generate(),
        $merchantId,
        CustomerId::generate(),
        PriceSnapshot::of(PriceId::generate(), ProductId::generate(), Money::of(1999, Currency::USD), BillingPeriod::of(BillingInterval::Month, 1)),
        SubscriptionStatus::Pending,
    );
    (new EloquentSubscriptionRepository(new SubscriptionMapper))->save($subscription);
    $payload = [
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'subscription_id' => $subscription->id()->toString(),
        'payment_id' => (string) Str::uuid(),
        'amount_minor_units' => 1999,
        'currency' => 'USD',
        'paid_at' => '2026-09-01T00:05:00+00:00',
    ];

    app(InvoicePaidConsumer::class)->handle((string) Str::uuid(), $payload);

    $persisted = app(ISubscriptionRepositoryPort::class)->get($subscription->id(), $merchantId);
    expect($persisted->status())->toBe(SubscriptionStatus::Active);
});
