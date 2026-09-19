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
use App\Infrastructure\Subscription\Adapters\Messaging\Consumers\InvoicePaymentFailedConsumer;
use App\Infrastructure\Subscription\Adapters\Persistence\Mappers\SubscriptionMapper;
use App\Infrastructure\Subscription\Adapters\Persistence\Repositories\EloquentSubscriptionRepository;
use App\Shared\Domain\ValueObjects\MerchantId;
use Illuminate\Support\Str;

test('handle marks the subscription PastDue', function () {
    $merchantId = MerchantId::generate();
    $subscription = Subscription::reconstitute(
        SubscriptionId::generate(),
        $merchantId,
        CustomerId::generate(),
        PriceSnapshot::of(PriceId::generate(), ProductId::generate(), Money::of(1999, Currency::USD), BillingPeriod::of(BillingInterval::Month, 1)),
        SubscriptionStatus::Active,
    );
    (new EloquentSubscriptionRepository(new SubscriptionMapper))->save($subscription);
    $payload = [
        'invoice_id' => (string) Str::uuid(),
        'merchant_id' => $merchantId->toString(),
        'customer_id' => (string) Str::uuid(),
        'subscription_id' => $subscription->id()->toString(),
        'payment_id' => (string) Str::uuid(),
        'failure_code' => 'card_declined',
    ];

    app(InvoicePaymentFailedConsumer::class)->handle((string) Str::uuid(), $payload);

    $persisted = app(ISubscriptionRepositoryPort::class)->get($subscription->id(), $merchantId);
    expect($persisted->status())->toBe(SubscriptionStatus::PastDue);
});
