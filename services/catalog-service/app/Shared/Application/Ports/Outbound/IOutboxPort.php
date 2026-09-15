<?php

namespace App\Shared\Application\Ports\Outbound;

use App\Shared\Application\IntegrationEvents\IntegrationEvent;
use App\Shared\Application\ReadModels\OutboxMessage;

interface IOutboxPort
{
    public function add(IntegrationEvent $event): void;

    /**
     * @return array<int, OutboxMessage>
     */
    public function unpublished(): array;

    public function markPublished(string $eventId): void;
}
