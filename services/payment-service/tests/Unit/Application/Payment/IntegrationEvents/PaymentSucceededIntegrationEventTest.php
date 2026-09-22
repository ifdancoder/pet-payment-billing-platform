<?php

use App\Application\Payment\IntegrationEvents\PaymentSucceededIntegrationEvent;
use App\Domain\Payment\Events\PaymentSucceeded;
use App\Domain\Payment\ValueObjects\Currency;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\Money;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Domain\Payment\ValueObjects\ProviderReference;
use App\Shared\Domain\ValueObjects\MerchantId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $paymentId = PaymentId::generate();
    $invoiceId = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $paidAt = new DateTimeImmutable('2026-09-05T12:00:00+00:00');
    $domainEvent = new PaymentSucceeded(
        $paymentId,
        $invoiceId,
        $merchantId,
        $customerId,
        Money::of(1999, Currency::USD),
        PaymentAttemptId::generate(),
        ProviderReference::of('fake:provider-ref'),
        $paidAt,
    );

    $integrationEvent = PaymentSucceededIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('payment.succeeded.v1')
        ->and($integrationEvent->aggregateType())->toBe('payment')
        ->and($integrationEvent->aggregateId())->toBe($paymentId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'payment_id' => $paymentId->toString(),
            'invoice_id' => $invoiceId->toString(),
            'merchant_id' => $merchantId->toString(),
            'customer_id' => $customerId->toString(),
            'amount_minor_units' => 1999,
            'currency' => 'USD',
            'paid_at' => $paidAt->format(DATE_ATOM),
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new PaymentSucceeded(
        PaymentId::generate(),
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        Money::of(1999, Currency::USD),
        PaymentAttemptId::generate(),
        ProviderReference::of('fake:provider-ref'),
        new DateTimeImmutable,
    );

    $a = PaymentSucceededIntegrationEvent::fromDomainEvent($domainEvent);
    $b = PaymentSucceededIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
