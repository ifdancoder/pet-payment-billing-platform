<?php

namespace App\Infrastructure\Subscription\Adapters\Messaging\Consumers;

use App\Application\Subscription\Commands\HandleInvoicePaid\HandleInvoicePaidCommand;
use App\Application\Subscription\Commands\HandleInvoicePaid\HandleInvoicePaidHandler;

/**
 * Translates a decoded invoice.paid.v1 message into a
 * HandleInvoicePaidCommand. Deliberately has no idea RabbitMQ exists —
 * it takes plain data, so it's testable without an AMQPMessage at all.
 */
final class InvoicePaidConsumer
{
    public function __construct(private readonly HandleInvoicePaidHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $this->handler->handle(new HandleInvoicePaidCommand(
            $eventId,
            'invoice.paid.v1',
            $payload['subscription_id'],
            $payload['merchant_id'],
        ));
    }
}
