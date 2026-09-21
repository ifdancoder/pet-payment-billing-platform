<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentFailedConsumer;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\SubscriptionCreatedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use DateTimeImmutable;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use RuntimeException;

/**
 * One queue per consuming service, not per event type (see
 * docs/adr/0002-rabbitmq-messaging.md): billing-service consumes three
 * event types today, so this binds every routing key to the same queue
 * and dispatches by the delivered message's routing key, rather than
 * running a separate queue/command per event.
 */
final class ConsumeBillingEventsCommand extends Command
{
    private const QUEUE = 'billing.events.v1';

    private const ROUTING_KEYS = ['subscription.created.v1', 'payment.succeeded.v1', 'payment.failed.v1'];

    protected $signature = 'billing-events:consume';

    protected $description = 'Drain up to one pending billing.events.v1 message from the queue and process it.';

    public function handle(
        AMQPChannel $channel,
        SubscriptionCreatedConsumer $subscriptionCreatedConsumer,
        PaymentSucceededConsumer $paymentSucceededConsumer,
        PaymentFailedConsumer $paymentFailedConsumer,
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
            'payment.failed.v1' => $paymentFailedConsumer->handle($headers['event_id'], $payload),
            default => throw new RuntimeException("Unroutable message with routing key \"{$message->getRoutingKey()}\" on queue \"".self::QUEUE.'".'),
        };

        // Deliberately logged before ack, not after: the only externally
        // observable signal that the business transaction has committed
        // but the message hasn't been acknowledged yet. Useful for
        // diagnosing a stuck ack in production; also the only thing a
        // test can watch for to kill this process at exactly that point
        // and prove Inbox/RabbitMQ redelivery actually recovers from a
        // real crash there, not just a manually duplicated event — see
        // tests/resilience/consumer-crash/.
        $this->info("Processed event {$headers['event_id']}, acking.");

        // Unset (0) in every real environment — this widens the window
        // above just enough for an external test to reliably observe
        // the log line and kill this process before the ack below,
        // instead of racing a gap that's normally microseconds wide.
        // tests/resilience/consumer-crash/ is the only place that sets
        // CONSUMER_CRASH_TEST_DELAY_MS.
        if ($delayMs = (int) env('CONSUMER_CRASH_TEST_DELAY_MS', 0)) {
            usleep($delayMs * 1000);
        }

        $message->ack();

        $this->info('Consumed 1 message(s).');

        return self::SUCCESS;
    }
}
