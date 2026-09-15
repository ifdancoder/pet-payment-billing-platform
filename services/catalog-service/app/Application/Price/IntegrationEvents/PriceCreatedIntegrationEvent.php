<?php

namespace App\Application\Price\IntegrationEvents;

use App\Domain\Price\Events\PriceCreated;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class PriceCreatedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $priceId,
        private readonly string $productId,
        private readonly int $amountMinorUnits,
        private readonly string $currency,
        private readonly string $type,
        private readonly ?string $billingInterval,
        private readonly ?int $billingIntervalCount,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(PriceCreated $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->priceId->toString(),
            $event->productId->toString(),
            $event->money->amountMinorUnits(),
            $event->money->currency()->value,
            $event->type->label(),
            $event->billingPeriod?->interval()->label(),
            $event->billingPeriod?->count(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'price.created.v1';
    }

    public function aggregateType(): string
    {
        return 'price';
    }

    public function aggregateId(): string
    {
        return $this->priceId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * @return array<string, string|int|null>
     */
    public function payload(): array
    {
        return [
            'price_id' => $this->priceId,
            'product_id' => $this->productId,
            'amount_minor_units' => $this->amountMinorUnits,
            'currency' => $this->currency,
            'type' => $this->type,
            'billing_interval' => $this->billingInterval,
            'billing_interval_count' => $this->billingIntervalCount,
        ];
    }
}
