<?php

use App\Application\Subscription\IntegrationEvents\SubscriptionRenewalDueIntegrationEvent;
use App\Domain\Subscription\Events\SubscriptionRenewalDue;
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

test('fromDomainEvent maps the renewal billing cycle and generates a fresh event id', function () {
    $event = new SubscriptionRenewalDue(
        $subscriptionId = SubscriptionId::generate(),
        $merchantId = MerchantId::generate(),
        $customerId = CustomerId::generate(),
        $snapshot = PriceSnapshot::of(
            $priceId = PriceId::generate(),
            $productId = ProductId::generate(),
            Money::of(1999, Currency::USD),
            BillingPeriod::of(BillingInterval::Month, 1),
        ),
        new DateTimeImmutable('2026-10-01T00:00:00+00:00'),
        new DateTimeImmutable('2026-10-01T00:00:01+00:00'),
    );

    $integrationEvent = SubscriptionRenewalDueIntegrationEvent::fromDomainEvent($event);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('subscription.renewal_due.v1')
        ->and($integrationEvent->aggregateType())->toBe('subscription')
        ->and($integrationEvent->aggregateId())->toBe($subscriptionId->toString())
        ->and($integrationEvent->occurredAt())->toBe($event->occurredAt)
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
            'period_start' => '2026-10-01T00:00:00+00:00',
        ]);

    expect(SubscriptionRenewalDueIntegrationEvent::fromDomainEvent($event)->eventId())
        ->not->toBe($integrationEvent->eventId());
});
