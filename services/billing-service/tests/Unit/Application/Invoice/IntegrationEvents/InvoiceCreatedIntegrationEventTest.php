<?php

use App\Application\Invoice\IntegrationEvents\InvoiceCreatedIntegrationEvent;
use App\Domain\Invoice\Events\InvoiceCreated;
use App\Domain\Invoice\ValueObjects\BillingPeriod;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $invoiceId = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $subscriptionId = SubscriptionId::generate();
    $period = BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00'));
    $domainEvent = new InvoiceCreated(
        $invoiceId,
        $merchantId,
        $customerId,
        $subscriptionId,
        $period,
        Money::of(1999, Currency::USD),
        Money::of(1999, Currency::USD),
    );

    $integrationEvent = InvoiceCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('invoice.created.v1')
        ->and($integrationEvent->aggregateType())->toBe('invoice')
        ->and($integrationEvent->aggregateId())->toBe($invoiceId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'invoice_id' => $invoiceId->toString(),
            'merchant_id' => $merchantId->toString(),
            'customer_id' => $customerId->toString(),
            'subscription_id' => $subscriptionId->toString(),
            'amount_minor_units' => 1999,
            'currency' => 'USD',
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $period = BillingPeriod::of(new DateTimeImmutable('2026-09-01T00:00:00+00:00'), new DateTimeImmutable('2026-10-01T00:00:00+00:00'));
    $domainEvent = new InvoiceCreated(
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        SubscriptionId::generate(),
        $period,
        Money::of(1999, Currency::USD),
        Money::of(1999, Currency::USD),
    );

    $a = InvoiceCreatedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = InvoiceCreatedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
