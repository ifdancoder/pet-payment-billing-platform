<?php

use App\Application\Invoice\IntegrationEvents\InvoicePaymentFailedIntegrationEvent;
use App\Domain\Invoice\ValueObjects\CustomerId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\PaymentId;
use App\Domain\Invoice\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use Ramsey\Uuid\Uuid;

test('of maps every argument and generates a fresh event id', function () {
    $invoiceId = InvoiceId::generate();
    $merchantId = MerchantId::generate();
    $customerId = CustomerId::generate();
    $subscriptionId = SubscriptionId::generate();
    $paymentId = PaymentId::generate();

    $integrationEvent = InvoicePaymentFailedIntegrationEvent::of(
        $invoiceId->toString(),
        $merchantId->toString(),
        $customerId->toString(),
        $subscriptionId->toString(),
        $paymentId->toString(),
        'card_declined',
    );

    expect(Uuid::isValid($integrationEvent->eventId()))->toBeTrue()
        ->and($integrationEvent->eventType())->toBe('invoice.payment_failed.v1')
        ->and($integrationEvent->aggregateType())->toBe('invoice')
        ->and($integrationEvent->aggregateId())->toBe($invoiceId->toString())
        ->and($integrationEvent->payload())->toBe([
            'invoice_id' => $invoiceId->toString(),
            'merchant_id' => $merchantId->toString(),
            'customer_id' => $customerId->toString(),
            'subscription_id' => $subscriptionId->toString(),
            'payment_id' => $paymentId->toString(),
            'failure_code' => 'card_declined',
        ]);
});

test('two integration events built from the same arguments get different event ids', function () {
    $args = [
        InvoiceId::generate()->toString(),
        MerchantId::generate()->toString(),
        CustomerId::generate()->toString(),
        SubscriptionId::generate()->toString(),
        PaymentId::generate()->toString(),
        'card_declined',
    ];

    $a = InvoicePaymentFailedIntegrationEvent::of(...$args);
    $b = InvoicePaymentFailedIntegrationEvent::of(...$args);

    expect($a->eventId())->not->toBe($b->eventId());
});
