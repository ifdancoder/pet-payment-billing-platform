<?php

use App\Application\Price\IntegrationEvents\PriceCreatedIntegrationEvent;
use App\Domain\Price\Events\PriceCreated;
use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Currency;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps a recurring price', function () {
    $priceId = PriceId::generate();
    $productId = ProductId::generate();
    $domainEvent = new PriceCreated(
        $priceId,
        $productId,
        Money::of(1999, Currency::USD),
        PriceType::Recurring,
        BillingPeriod::of(BillingInterval::Month, 1),
    );

    $integrationEvent = PriceCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('price.created.v1')
        ->and($integrationEvent->aggregateType())->toBe('price')
        ->and($integrationEvent->aggregateId())->toBe($priceId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'price_id' => $priceId->toString(),
            'product_id' => $productId->toString(),
            'amount_minor_units' => 1999,
            'currency' => 'USD',
            'type' => 'recurring',
            'billing_interval' => 'month',
            'billing_interval_count' => 1,
        ]);
});

test('fromDomainEvent maps a one-time price with null billing period fields', function () {
    $domainEvent = new PriceCreated(
        PriceId::generate(),
        ProductId::generate(),
        Money::of(4999, Currency::USD),
        PriceType::OneTime,
        null,
    );

    $integrationEvent = PriceCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($integrationEvent->payload()['type'])->toBe('one_time')
        ->and($integrationEvent->payload()['billing_interval'])->toBeNull()
        ->and($integrationEvent->payload()['billing_interval_count'])->toBeNull();
});
