<?php

namespace App\Application\Payment\IntegrationEvents;

use App\Domain\Payment\Events\PaymentFailed;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class PaymentFailedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $paymentId,
        private readonly string $invoiceId,
        private readonly string $merchantId,
        private readonly string $customerId,
        private readonly string $failureCode,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(PaymentFailed $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->paymentId->toString(),
            $event->invoiceId->toString(),
            $event->merchantId->toString(),
            $event->customerId->toString(),
            $event->failureCode,
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'payment.failed.v1';
    }

    public function aggregateType(): string
    {
        return 'payment';
    }

    public function aggregateId(): string
    {
        return $this->paymentId;
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
            'payment_id' => $this->paymentId,
            'invoice_id' => $this->invoiceId,
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'failure_code' => $this->failureCode,
        ];
    }
}
