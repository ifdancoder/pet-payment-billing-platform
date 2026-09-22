<?php

use App\Application\Invoice\IntegrationEvents\InvoicePaidIntegrationEvent;
use App\Domain\Invoice\Events\InvoicePaid;
use App\Domain\Invoice\ValueObjects\Currency;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\Money;
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $invoiceId = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $subscriptionId = SubscriptionId::generate();
    $paymentId = PaymentId::generate();
    $paidAt = new DateTimeImmutable('2026-09-05T12:00:00+00:00');
    $domainEvent = new InvoicePaid(
        $invoiceId,
        $merchantId,
        $customerId,
        $subscriptionId,
        $paymentId,
        Money::of(1999, Currency::USD),
        $paidAt,
    );

    $integrationEvent = InvoicePaidIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('invoice.paid.v1')
        ->and($integrationEvent->aggregateType())->toBe('invoice')
        ->and($integrationEvent->aggregateId())->toBe($invoiceId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'invoice_id' => $invoiceId->toString(),
            'merchant_id' => $merchantId->toString(),
            'customer_id' => $customerId->toString(),
            'subscription_id' => $subscriptionId->toString(),
            'payment_id' => $paymentId->toString(),
            'amount_minor_units' => 1999,
            'currency' => 'USD',
            'paid_at' => $paidAt->format(DATE_ATOM),
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new InvoicePaid(
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        SubscriptionId::generate(),
        PaymentId::generate(),
        Money::of(1999, Currency::USD),
        new DateTimeImmutable,
    );

    $a = InvoicePaidIntegrationEvent::fromDomainEvent($domainEvent);
    $b = InvoicePaidIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
