<?php

namespace Tests\Support;

use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use DateTimeImmutable;

/**
 * A minimal IntegrationEvent for exercising the outbox mechanics
 * (EloquentOutbox, PublishOutboxMessagesHandler, outbox:publish) without
 * depending on a real domain integration event.
 */
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
        return 'subscription.created.v1';
    }

    public function aggregateType(): string
    {
        return 'subscription';
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
        return ['status' => 'pending'];
    }
}
