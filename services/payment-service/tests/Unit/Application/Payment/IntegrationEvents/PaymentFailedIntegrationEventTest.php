<?php

use App\Application\Payment\IntegrationEvents\PaymentFailedIntegrationEvent;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\Payment\ValueObjects\CustomerId;
use App\Domain\Payment\ValueObjects\InvoiceId;
use App\Domain\Payment\ValueObjects\PaymentAttemptId;
use App\Domain\Payment\ValueObjects\PaymentId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Ramsey\Uuid\Uuid;

test('fromDomainEvent maps every field and generates a fresh event id', function () {
    $paymentId = PaymentId::generate();
    $invoiceId = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $domainEvent = new PaymentFailed(
        $paymentId,
        $invoiceId,
        $merchantId,
        $customerId,
        PaymentAttemptId::generate(),
        'card_declined',
        new DateTimeImmutable,
    );

    $integrationEvent = PaymentFailedIntegrationEvent::fromDomainEvent($domainEvent);

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('payment.failed.v1')
        ->and($integrationEvent->aggregateType())->toBe('payment')
        ->and($integrationEvent->aggregateId())->toBe($paymentId->toString())
        ->and($integrationEvent->occurredAt())->toBe($domainEvent->occurredAt)
        ->and($integrationEvent->payload())->toBe([
            'payment_id' => $paymentId->toString(),
            'invoice_id' => $invoiceId->toString(),
            'merchant_id' => $merchantId->toString(),
            'customer_id' => $customerId->toString(),
            'failure_code' => 'card_declined',
        ]);
});

test('two integration events built from the same domain event get different event ids', function () {
    $domainEvent = new PaymentFailed(
        PaymentId::generate(),
        InvoiceId::generate(),
        MerchantId::generate(),
        CustomerId::generate(),
        PaymentAttemptId::generate(),
        'card_declined',
        new DateTimeImmutable,
    );

    $a = PaymentFailedIntegrationEvent::fromDomainEvent($domainEvent);
    $b = PaymentFailedIntegrationEvent::fromDomainEvent($domainEvent);

    expect($a->eventId())->not->toBe($b->eventId());
});
