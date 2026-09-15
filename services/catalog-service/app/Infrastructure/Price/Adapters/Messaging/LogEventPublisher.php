<?php

namespace App\Infrastructure\Price\Adapters\Messaging;

use App\Application\Price\Ports\Outbound\IEventPublisherPort;
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
