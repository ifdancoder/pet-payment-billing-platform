<?php

namespace App\Infrastructure\Subscription\Adapters\Messaging\Consumers;

use App\Application\Subscription\Commands\HandleInvoicePaymentFailed\HandleInvoicePaymentFailedCommand;
use App\Application\Subscription\Commands\HandleInvoicePaymentFailed\HandleInvoicePaymentFailedHandler;

/**
 * Translates a decoded invoice.payment_failed.v1 message into a
 * HandleInvoicePaymentFailedCommand. Deliberately has no idea RabbitMQ
 * exists — it takes plain data, so it's testable without an AMQPMessage
 * at all.
 */
final class InvoicePaymentFailedConsumer
{
    public function __construct(private readonly HandleInvoicePaymentFailedHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $this->handler->handle(new HandleInvoicePaymentFailedCommand(
            $eventId,
            'invoice.payment_failed.v1',
            $payload['subscription_id'],
            $payload['merchant_id'],
        ));
    }
}
