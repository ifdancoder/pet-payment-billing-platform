<?php

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\ReadModels\OutboxMessage;
use Illuminate\Support\Facades\Log;

/**
 * Stand-in for the RabbitMQ adapter, used in the testing environment so
 * tests never need a live broker. Called only by the outbox publisher
 * process, never directly by a handler.
 */
final class LogEventPublisher implements IEventPublisherPort
{
    public function publish(OutboxMessage $message): void
    {
        Log::info("Domain event published: {$message->eventType} ({$message->eventId})");
    }
}
