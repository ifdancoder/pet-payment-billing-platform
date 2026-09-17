<?php

namespace App\Infrastructure\Invoice\Adapters\Messaging\Consumers;

use App\Application\Invoice\Commands\CreateInvoice\CreateInvoiceCommand;
use App\Application\Invoice\Commands\CreateInvoice\CreateInvoiceHandler;
use DateTimeImmutable;

/**
 * Translates a decoded subscription.created.v1 message into a
 * CreateInvoiceCommand. Deliberately has no idea RabbitMQ exists — it
 * takes plain data, so it's testable without an AMQPMessage at all. The
 * AMQP-facing adapter is responsible for decoding the message and acking
 * it once this returns without throwing.
 */
final class SubscriptionCreatedConsumer
{
    public function __construct(private readonly CreateInvoiceHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload, DateTimeImmutable $occurredAt): void
    {
        $this->handler->handle(new CreateInvoiceCommand(
            $eventId,
            'subscription.created.v1',
            $payload['merchant_id'],
            $payload['customer_id'],
            $payload['subscription_id'],
            $payload['product_id'] ?? null,
            $payload['price_id'] ?? null,
            // subscription.created.v1 doesn't carry a product name/description
            // snapshot yet, so this line has no display-friendly label beyond
            // a generic one until that's added upstream in subscription-service.
            'Subscription',
            $payload['amount_minor_units'],
            $payload['currency'],
            $payload['billing_interval'],
            $payload['billing_interval_count'],
            $occurredAt,
        ));
    }
}
