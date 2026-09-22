<?php

namespace App\Application\Invoice\IntegrationEvents;

use App\Domain\Invoice\Events\InvoiceCreated;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Payment only needs enough to charge the invoice, never the full Invoice
 * (lines, subtotal, currency breakdown, ...) — so this stays intentionally
 * thin rather than mirroring the aggregate.
 */
final class InvoiceCreatedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $invoiceId,
        private readonly string $merchantId,
        private readonly string $customerId,
        private readonly string $subscriptionId,
        private readonly int $amountMinorUnits,
        private readonly string $currency,
        private readonly string $billingReason,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(InvoiceCreated $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->invoiceId->toString(),
            $event->merchantId->toString(),
            $event->customerId->toString(),
            $event->subscriptionId->toString(),
            $event->total->amountMinorUnits(),
            $event->total->currency()->value,
            $event->renewal ? 'subscription_cycle' : 'subscription_create',
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'invoice.created.v1';
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
     * @return array<string, string|int>
     */
    public function payload(): array
    {
        return [
            'invoice_id' => $this->invoiceId,
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'subscription_id' => $this->subscriptionId,
            'amount_minor_units' => $this->amountMinorUnits,
            'currency' => $this->currency,
            'billing_reason' => $this->billingReason,
        ];
    }
}
