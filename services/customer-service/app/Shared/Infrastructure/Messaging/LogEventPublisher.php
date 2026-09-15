<?php

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\ReadModels\OutboxMessage;
use Illuminate\Support\Facades\Log;

/**
 * Stand-in for a real broker adapter (RabbitMQ). Just logs for now, so
 * outbox messages have somewhere to go without pulling in a new dependency.
 * Called only by the outbox publisher process, never directly by a handler.
 */
final class LogEventPublisher implements IEventPublisherPort
{
    public function publish(OutboxMessage $message): void
    {
        Log::info("Domain event published: {$message->eventType} ({$message->eventId})");
    }
}
