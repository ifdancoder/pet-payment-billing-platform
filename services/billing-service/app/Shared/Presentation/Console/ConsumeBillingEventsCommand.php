<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\SubscriptionCreatedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use DateTimeImmutable;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use RuntimeException;

/**
 * One queue per consuming service, not per event type (see
 * docs/adr/0002-rabbitmq-messaging.md): billing-service consumes two
 * event types today, so this binds both routing keys to the same queue
 * and dispatches by the delivered message's routing key, rather than
 * running a separate queue/command per event.
 */
final class ConsumeBillingEventsCommand extends Command
{
    private const QUEUE = 'billing.events.v1';

    private const ROUTING_KEYS = ['subscription.created.v1', 'payment.succeeded.v1'];

    protected $signature = 'billing-events:consume';

    protected $description = 'Drain up to one pending billing.events.v1 message from the queue and process it.';

    public function handle(
        AMQPChannel $channel,
        SubscriptionCreatedConsumer $subscriptionCreatedConsumer,
        PaymentSucceededConsumer $paymentSucceededConsumer,
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
            'subscription.created.v1' => $subscriptionCreatedConsumer->handle($headers['event_id'], $payload, new DateTimeImmutable($headers['occurred_at'])),
            'payment.succeeded.v1' => $paymentSucceededConsumer->handle($headers['event_id'], $payload),
            default => throw new RuntimeException("Unroutable message with routing key \"{$message->getRoutingKey()}\" on queue \"".self::QUEUE.'".'),
        };

        $message->ack();

        $this->info('Consumed 1 message(s).');

        return self::SUCCESS;
    }
}
