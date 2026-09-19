<?php

namespace App\Application\Invoice\IntegrationEvents;

use App\Domain\Invoice\Events\InvoicePaid;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class InvoicePaidIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $invoiceId,
        private readonly string $merchantId,
        private readonly string $customerId,
        private readonly string $subscriptionId,
        private readonly string $paymentId,
        private readonly int $amountMinorUnits,
        private readonly string $currency,
        private readonly DateTimeImmutable $paidAt,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(InvoicePaid $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->invoiceId->toString(),
            $event->merchantId->toString(),
            $event->customerId->toString(),
            $event->subscriptionId->toString(),
            $event->paymentId->toString(),
            $event->total->amountMinorUnits(),
            $event->total->currency()->value,
            $event->paidAt,
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'invoice.paid.v1';
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
            'payment_id' => $this->paymentId,
            'amount_minor_units' => $this->amountMinorUnits,
            'currency' => $this->currency,
            'paid_at' => $this->paidAt->format(DATE_ATOM),
        ];
    }
}
