<?php

namespace App\Shared\Application\ReadModels;

use DateTimeImmutable;

final class OutboxMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $aggregateType,
        public readonly string $aggregateId,
        public readonly array $payload,
        public readonly DateTimeImmutable $occurredAt,
    ) {}
}
