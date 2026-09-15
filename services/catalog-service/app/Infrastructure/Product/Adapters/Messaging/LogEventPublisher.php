<?php

namespace App\Infrastructure\Product\Adapters\Messaging;

use App\Application\Product\Ports\Outbound\IEventPublisherPort;
use Illuminate\Support\Facades\Log;

/**
 * Stand-in for a real broker adapter (RabbitMQ). Just logs for now, so
 * domain events have somewhere to go without pulling in a new dependency.
 */
final class LogEventPublisher implements IEventPublisherPort
{
    public function publish(object $event): void
    {
        Log::info('Domain event published: '.$event::class);
    }
}
