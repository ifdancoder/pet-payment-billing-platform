<?php

namespace App\Application\Payment\IntegrationEvents;

use App\Domain\Payment\Events\PaymentSucceeded;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class PaymentSucceededIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $paymentId,
        private readonly string $invoiceId,
        private readonly string $merchantId,
        private readonly string $customerId,
        private readonly int $amountMinorUnits,
        private readonly string $currency,
        private readonly DateTimeImmutable $paidAt,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(PaymentSucceeded $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->paymentId->toString(),
            $event->invoiceId->toString(),
            $event->merchantId->toString(),
            $event->customerId->toString(),
            $event->money->amountMinorUnits(),
            $event->money->currency()->value,
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
        return 'payment.succeeded.v1';
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
     * @return array<string, string|int>
     */
    public function payload(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'invoice_id' => $this->invoiceId,
            'merchant_id' => $this->merchantId,
            'customer_id' => $this->customerId,
            'amount_minor_units' => $this->amountMinorUnits,
            'currency' => $this->currency,
            'paid_at' => $this->paidAt->format(DATE_ATOM),
        ];
    }
}
