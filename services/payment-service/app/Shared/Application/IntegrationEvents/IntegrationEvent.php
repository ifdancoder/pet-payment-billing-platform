<?php

namespace App\Shared\Application\IntegrationEvents;

use DateTimeImmutable;

interface IntegrationEvent
{
    public function eventId(): string;

    public function eventType(): string;

    public function aggregateType(): string;

    public function aggregateId(): string;

    public function occurredAt(): DateTimeImmutable;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
