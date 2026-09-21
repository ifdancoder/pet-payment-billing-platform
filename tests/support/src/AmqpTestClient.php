<?php

namespace BillingPlatform\TestSupport;

use DateTimeImmutable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

/**
 * A generic test-side client for the `billing.events` exchange every
 * service integration test needs on one side or the other: publishing
 * an event directly (standing in for an upstream service's own Outbox,
 * when pulling that service into the stack just to produce one event
 * would make the test 3+ services for no reason — see
 * tests/integration/billing-to-payment/README.md) and/or asserting a
 * downstream service actually republished one, by binding a temporary
 * queue and reading from it.
 *
 * Not tied to any one event's payload shape — building the right
 * envelope (aggregate_type, aggregate_id, payload fields) is each
 * test's own job; this only handles the wire mechanics, which are
 * identical for every event per docs/adr/0002-rabbitmq-messaging.md.
 */
final class AmqpTestClient
{
    private const EXCHANGE = 'billing.events';

    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
    ) {}

    public static function fromEnv(): self
    {
        return new self(
            getenv('RABBITMQ_HOST') ?: 'localhost',
            (int) (getenv('RABBITMQ_PORT') ?: 25672),
            getenv('RABBITMQ_USER') ?: 'billing',
            getenv('RABBITMQ_PASSWORD') ?: 'billing',
        );
    }

    /**
     * Publishes directly onto the exchange. Returns the event_id so the
     * caller can assert on it downstream (e.g. Inbox dedup).
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $routingKey, string $aggregateType, string $aggregateId, array $payload, ?DateTimeImmutable $occurredAt = null): string
    {
        $eventId = Uuid::uuid4()->toString();

        $this->channel()->basic_publish(
            new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    'event_id' => $eventId,
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $aggregateId,
                    'occurred_at' => ($occurredAt ?? new DateTimeImmutable())->format(DATE_ATOM),
                ]),
            ]),
            self::EXCHANGE,
            $routingKey,
        );

        return $eventId;
    }

    /**
     * Declares and binds the exact queue name and routing key a real
     * consumer command uses, before this client publishes to it.
     * Idempotent — safe even if the real consumer has already done the
     * same declare/bind itself. Without this, publishing before that
     * consumer's own first loop iteration has run would silently drop
     * the message: a topic exchange has nowhere to route it yet.
     */
    public function ensureConsumerQueueBound(string $queueName, string $routingKey): void
    {
        $this->channel()->queue_declare($queueName, false, true, false, false);
        $this->channel()->queue_bind($queueName, self::EXCHANGE, $routingKey);
    }

    /**
     * Declares a private, throwaway queue bound to one routing key, for
     * asserting that some other service actually republished an event
     * — not a real consumer's own queue, so it doesn't compete with it
     * for messages. Returns the queue name for use with readMessage().
     */
    public function bindTestQueue(string $routingKey): string
    {
        $queue = 'test.'.str_replace('.', '_', $routingKey).'.'.bin2hex(random_bytes(4));

        // exclusive + auto_delete: cleaned up automatically when this
        // connection closes, so a crashed or interrupted test run
        // doesn't leave orphaned queues behind.
        $this->channel()->queue_declare($queue, false, false, true, true);
        $this->channel()->queue_bind($queue, self::EXCHANGE, $routingKey);

        return $queue;
    }

    /**
     * One non-blocking read from a queue. Returns null if nothing is
     * waiting — pair with eventually() to poll for an async publish
     * rather than blocking here.
     *
     * @return array{event_id: string, aggregate_type: string, aggregate_id: string, occurred_at: string, payload: array<string, mixed>}|null
     */
    public function readMessage(string $queue): ?array
    {
        $message = $this->channel()->basic_get($queue);

        if ($message === null) {
            return null;
        }

        $headers = $message->get('application_headers')->getNativeData();
        $message->ack();

        return [
            'event_id' => $headers['event_id'],
            'aggregate_type' => $headers['aggregate_type'],
            'aggregate_id' => $headers['aggregate_id'],
            'occurred_at' => $headers['occurred_at'],
            'payload' => json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR),
        ];
    }

    private function channel(): AMQPChannel
    {
        if ($this->channel !== null) {
            return $this->channel;
        }

        $connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        $channel = $connection->channel();
        $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);

        return $this->channel = $channel;
    }
}
