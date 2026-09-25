<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentFailedConsumer;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\SubscriptionCreatedConsumer;
use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\SubscriptionRenewalDueConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use DateTimeImmutable;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use Platform\Messaging\ReliableRabbitMqConsumer;
use RuntimeException;

final class ConsumeBillingEventsCommand extends Command
{
    private const QUEUE = 'billing.events.v1';

    private const ROUTING_KEYS = ['subscription.created.v1', 'subscription.renewal_due.v1', 'payment.succeeded.v1', 'payment.failed.v1'];

    protected $signature = 'billing-events:consume';

    protected $description = 'Drain up to one pending billing.events.v1 message from the queue and process it.';

    public function handle(
        AMQPChannel $channel,
        SubscriptionCreatedConsumer $subscriptionCreatedConsumer,
        SubscriptionRenewalDueConsumer $subscriptionRenewalDueConsumer,
        PaymentSucceededConsumer $paymentSucceededConsumer,
        PaymentFailedConsumer $paymentFailedConsumer,
        ReliableRabbitMqConsumer $reliable,
    ): int {
        $result = $reliable->consumeOne($channel, self::QUEUE, RabbitMqEventPublisher::EXCHANGE, self::ROUTING_KEYS,
            function ($message, array $headers, array $payload) use ($subscriptionCreatedConsumer, $subscriptionRenewalDueConsumer, $paymentSucceededConsumer, $paymentFailedConsumer): void {
                match ($message->getRoutingKey()) {
                    'subscription.created.v1' => $subscriptionCreatedConsumer->handle($headers['event_id'], $payload, new DateTimeImmutable($headers['occurred_at'])),
                    'subscription.renewal_due.v1' => $subscriptionRenewalDueConsumer->handle($headers['event_id'], $payload),
                    'payment.succeeded.v1' => $paymentSucceededConsumer->handle($headers['event_id'], $payload),
                    'payment.failed.v1' => $paymentFailedConsumer->handle($headers['event_id'], $payload),
                    default => throw new RuntimeException("Unroutable message with routing key \"{$message->getRoutingKey()}\" on queue \"".self::QUEUE.'".'),
                };

                // The crash-recovery test uses this pre-ack signal to kill the
                // process after commit and verify RabbitMQ redelivery.
                $this->info("Processed event {$headers['event_id']}, acking.");

                // Unset outside tests. It makes the commit-to-ack window observable.
                if ($delayMs = (int) env('CONSUMER_CRASH_TEST_DELAY_MS', 0)) {
                    usleep($delayMs * 1000);
                }
            });
        $this->info($result === 'empty' ? 'Consumed 0 message(s).' : ($result === 'processed' ? 'Consumed 1 message(s).' : "Message {$result}."));

        return self::SUCCESS;
    }
}
