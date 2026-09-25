<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Subscription\Adapters\Messaging\Consumers\InvoicePaidConsumer;
use App\Infrastructure\Subscription\Adapters\Messaging\Consumers\InvoicePaymentFailedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use Platform\Messaging\ReliableRabbitMqConsumer;
use RuntimeException;

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
        ReliableRabbitMqConsumer $reliable,
    ): int {
        $result = $reliable->consumeOne($channel, self::QUEUE, RabbitMqEventPublisher::EXCHANGE, self::ROUTING_KEYS,
            function ($message, array $headers, array $payload) use ($invoicePaidConsumer, $invoicePaymentFailedConsumer): void {
                match ($message->getRoutingKey()) {
                    'invoice.paid.v1' => $invoicePaidConsumer->handle($headers['event_id'], $payload),
                    'invoice.payment_failed.v1' => $invoicePaymentFailedConsumer->handle($headers['event_id'], $payload),
                    default => throw new RuntimeException("Unroutable message with routing key \"{$message->getRoutingKey()}\" on queue \"".self::QUEUE.'".'),
                };
            });
        $this->info($result === 'empty' ? 'Consumed 0 message(s).' : ($result === 'processed' ? 'Consumed 1 message(s).' : "Message {$result}."));

        return self::SUCCESS;
    }
}
