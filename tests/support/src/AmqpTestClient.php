<?php

namespace BillingPlatform\TestSupport;

use DateTimeImmutable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

final class AmqpTestClient
{
    private const EXCHANGE = 'billing.events';

    private const DEAD_LETTER_EXCHANGE = 'billing.events.dlx';

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
     * Pass an event ID to simulate redelivery of the same message.
     *
     * @param  array<string, mixed>  $payload
     */
    public function publish(string $routingKey, string $aggregateType, string $aggregateId, array $payload, ?DateTimeImmutable $occurredAt = null, ?string $eventId = null): string
    {
        $eventId ??= Uuid::uuid4()->toString();

        $this->channel()->basic_publish(
            new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    'event_id' => $eventId,
                    'aggregate_type' => $aggregateType,
                    'aggregate_id' => $aggregateId,
                    'occurred_at' => ($occurredAt ?? new DateTimeImmutable)->format(DATE_ATOM),
                ]),
            ]),
            self::EXCHANGE,
            $routingKey,
        );

        return $eventId;
    }

    /** Declares the queue before publish; an unbound topic exchange drops the message. */
    public function ensureConsumerQueueBound(string $queueName, string $routingKey): void
    {
        $channel = $this->channel();
        $deadLetterQueue = $queueName.'.dlq';

        $channel->exchange_declare(self::DEAD_LETTER_EXCHANGE, 'direct', false, true, false);
        $channel->queue_declare($deadLetterQueue, false, true, false, false);
        $channel->queue_bind($deadLetterQueue, self::DEAD_LETTER_EXCHANGE, $queueName);
        $channel->queue_declare($queueName, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => self::DEAD_LETTER_EXCHANGE,
            'x-dead-letter-routing-key' => $queueName,
        ]));
        $channel->queue_bind($queueName, self::EXCHANGE, $routingKey);
    }

    public function bindTestQueue(string $routingKey): string
    {
        $queue = 'test.'.str_replace('.', '_', $routingKey).'.'.bin2hex(random_bytes(4));

        // Exclusive, auto-delete queues do not survive an interrupted test run.
        $this->channel()->queue_declare($queue, false, false, true, true);
        $this->channel()->queue_bind($queue, self::EXCHANGE, $routingKey);

        return $queue;
    }

    /**
     * Performs one non-blocking read.
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
