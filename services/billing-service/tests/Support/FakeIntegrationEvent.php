<?php

namespace Tests\Support;

use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;

final class FakeIntegrationEvent implements IntegrationEvent
{
    public function __construct(
        private readonly string $eventId,
        private readonly string $aggregateId = '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        private readonly DateTimeImmutable $occurredAt = new DateTimeImmutable,
    ) {}

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
        return $this->aggregateId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function payload(): array
    {
        return ['status' => 'open'];
    }
}
