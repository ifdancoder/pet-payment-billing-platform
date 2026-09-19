<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Subscription\Adapters\Messaging\Consumers\InvoicePaidConsumer;
use App\Infrastructure\Subscription\Adapters\Messaging\Consumers\InvoicePaymentFailedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use RuntimeException;

/**
 * One queue per consuming service, not per event type (see
 * docs/adr/0002-rabbitmq-messaging.md): subscription-service consumes
 * two event types today, so this binds both routing keys to the same
 * queue and dispatches by the delivered message's routing key.
 */
final class ConsumeSubscriptionEventsCommand extends Command
{
    private const QUEUE = 'subscription.events.v1';

    private const ROUTING_KEYS = ['invoice.paid.v1', 'invoice.payment_failed.v1'];

    protected $signature = 'subscription-events:consume';

    protected $description = 'Drain up to one pending subscription.events.v1 message from the queue and process it.';

    public function handle(
        AMQPChannel $channel,
        InvoicePaidConsumer $invoicePaidConsumer,
        InvoicePaymentFailedConsumer $invoicePaymentFailedConsumer,
    ): int {
        $channel->queue_declare(self::QUEUE, false, true, false, false);

        foreach (self::ROUTING_KEYS as $routingKey) {
            $channel->queue_bind(self::QUEUE, RabbitMqEventPublisher::EXCHANGE, $routingKey);
        }

        $message = $channel->basic_get(self::QUEUE);

        if ($message === null) {
            $this->info('Consumed 0 message(s).');

            return self::SUCCESS;
        }

        $headers = $message->get('application_headers')->getNativeData();
        $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

        match ($message->getRoutingKey()) {
            'invoice.paid.v1' => $invoicePaidConsumer->handle($headers['event_id'], $payload),
            'invoice.payment_failed.v1' => $invoicePaymentFailedConsumer->handle($headers['event_id'], $payload),
            default => throw new RuntimeException("Unroutable message with routing key \"{$message->getRoutingKey()}\" on queue \"".self::QUEUE.'".'),
        };

        $message->ack();

        $this->info('Consumed 1 message(s).');

        return self::SUCCESS;
    }
}
