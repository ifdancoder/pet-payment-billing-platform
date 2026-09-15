<?php

namespace App\Application\Product\IntegrationEvents;

use App\Domain\Product\Events\ProductArchived;
use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

final class ProductArchivedIntegrationEvent implements IntegrationEvent
{
    private function __construct(
        private readonly string $eventId,
        private readonly string $productId,
        private readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function fromDomainEvent(ProductArchived $event): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $event->productId->toString(),
            $event->occurredAt,
        );
    }

    public function eventId(): string
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return 'product.archived.v1';
    }

    public function aggregateType(): string
    {
        return 'product';
    }

    public function aggregateId(): string
    {
        return $this->productId;
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
            'product_id' => $this->productId,
        ];
    }
}
