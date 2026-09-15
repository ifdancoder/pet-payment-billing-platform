<?php

namespace App\Shared\Infrastructure\Messaging;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\ReadModels\OutboxMessage;
use Illuminate\Support\Facades\Log;

final class LogEventPublisher implements IEventPublisherPort
{
    public function publish(OutboxMessage $message): void
    {
        Log::info("Domain event published: {$message->eventType} ({$message->eventId})");
    }
}
