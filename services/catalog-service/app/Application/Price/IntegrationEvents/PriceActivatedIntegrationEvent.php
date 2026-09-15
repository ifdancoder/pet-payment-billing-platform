<?php

namespace App\Application\Price\IntegrationEvents;

use App\Domain\Price\Events\PriceActivated;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class PriceActivatedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $priceId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(PriceActivated $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->priceId->toString(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'price.activated.v1';
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
     * @return array<string, string>
     */
    public function payload(): array
    {
        return [
            'price_id' => $this->priceId,
        ];
    }
}
