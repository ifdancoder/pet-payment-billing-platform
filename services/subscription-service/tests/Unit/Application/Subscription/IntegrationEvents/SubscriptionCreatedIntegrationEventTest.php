<?php

use App\Application\Subscription\IntegrationEvents\SubscriptionCreatedIntegrationEvent;
use App\Domain\Subscription\Events\SubscriptionCreated;
use App\Domain\Subscription\ValueObjects\BillingInterval;
use App\Domain\Subscription\ValueObjects\BillingPeriod;
use App\Domain\Subscription\ValueObjects\Currency;
use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\Money;
use App\Domain\Subscription\ValueObjects\PriceId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\ProductId;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $subscriptionId = SubscriptionId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $priceId = PriceId::generate();
    $productId = ProductId::generate();
    $priceSnapshot = PriceSnapshot::of(
        $priceId,
        $productId,
        Money::of(1999, Currency::USD),
        BillingPeriod::of(BillingInterval::Month, 1),
    );
    $domainEvent = new SubscriptionCreated($subscriptionId, $merchantId, $customerId, $priceSnapshot);

    $integrationEvent = SubscriptionCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('subscription.created.v1')
        ->and($integrationEvent->aggregateType())->toBe('subscription')
        ->and($integrationEvent->aggregateId())->toBe($subscriptionId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'subscription_id' => $subscriptionId->toString(),
            'merchant_id' => $merchantId->toString(),
            'customer_id' => $customerId->toString(),
            'price_id' => $priceId->toString(),
            'product_id' => $productId->toString(),
            'amount_minor_units' => 1999,
            'currency' => 'USD',
            'billing_interval' => 'month',
            'billing_interval_count' => 1,
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new SubscriptionCreated(
        SubscriptionId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        PriceSnapshot::of(PriceId::generate(), ProductId::generate(), Money::of(1999, Currency::USD), BillingPeriod::of(BillingInterval::Month, 1)),
    );

    $a = SubscriptionCreatedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = SubscriptionCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
