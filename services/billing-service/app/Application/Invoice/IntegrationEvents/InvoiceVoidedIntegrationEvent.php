<?php

namespace App\Application\Invoice\IntegrationEvents;

use App\Domain\Invoice\Events\InvoiceVoided;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class InvoiceVoidedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $invoiceId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(InvoiceVoided $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->invoiceId->toString(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'invoice.voided.v1';
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
        ];
    }
}
