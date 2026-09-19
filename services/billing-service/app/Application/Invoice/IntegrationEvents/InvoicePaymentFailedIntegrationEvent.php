<?php

namespace App\Application\Invoice\IntegrationEvents;

use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * The translation hop for payment.failed.v1, mirroring
 * InvoicePaidIntegrationEvent — Billing doesn't have a domain event to
 * translate from here (the Invoice itself doesn't change state on a
 * failed payment, it just stays Open awaiting another attempt), so this
 * is built directly from the relay handler's inputs rather than via a
 * fromDomainEvent() factory.
 */
final class InvoicePaymentFailedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $invoiceId,
        private readonly string $merchantId,
        private readonly string $customerId,
        private readonly string $subscriptionId,
        private readonly string $paymentId,
        private readonly string $failureCode,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function of(
        string $invoiceId,
        string $merchantId,
        string $customerId,
        string $subscriptionId,
        string $paymentId,
        string $failureCode,
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $invoiceId,
            $merchantId,
            $customerId,
            $subscriptionId,
            $paymentId,
            $failureCode,
            new DateTimeImmutable,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'invoice.payment_failed.v1';
    }

    public function aggregateType(): string
    {
        return 'invoice';
    }

    public function aggregateId(): string
    {
        return $this->invoiceId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, string>
     */
    public function payload(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'subscription_id' => $this->subscriptionId,
            'payment_id' => $this->paymentId,
            'failure_code' => $this->failureCode,
        ];
    }
}
