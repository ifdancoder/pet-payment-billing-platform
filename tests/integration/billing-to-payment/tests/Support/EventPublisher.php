<?php

namespace Tests\Support;

use DateTimeImmutable;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

final class EventPublisher
{
    private const EXCHANGE = 'billing.events';

    private const DEAD_LETTER_EXCHANGE = 'billing.events.dlx';

    private const BILLING_QUEUE = 'billing.events.v1';

    private static ?AMQPChannel $channel = null;

    public static function publishSubscriptionCreated(array $payload): string
    {
        $eventId = Uuid::uuid4()->toString();

        self::channel()->basic_publish(
            new AMQPMessage(json_encode($payload, JSON_THROW_ON_ERROR), [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    'event_id' => $eventId,
                    'aggregate_type' => 'subscription',
                    'aggregate_id' => $payload['subscription_id'],
                    'occurred_at' => (new DateTimeImmutable)->format(DATE_ATOM),
                ]),
            ]),
            self::EXCHANGE,
            'subscription.created.v1',
        );

        return $eventId;
    }

    private static function channel(): AMQPChannel
    {
        if (self::$channel !== null) {
            return self::$channel;
        }

        $connection = new AMQPStreamConnection(
            getenv('RABBITMQ_HOST') ?: 'localhost',
            (int) (getenv('RABBITMQ_PORT') ?: 25672),
            getenv('RABBITMQ_USER') ?: 'billing',
            getenv('RABBITMQ_PASSWORD') ?: 'billing',
        );

        $channel = $connection->channel();
        $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);
        $deadLetterQueue = self::BILLING_QUEUE.'.dlq';
        $channel->exchange_declare(self::DEAD_LETTER_EXCHANGE, 'direct', false, true, false);
        $channel->queue_declare($deadLetterQueue, false, true, false, false);
        $channel->queue_bind($deadLetterQueue, self::DEAD_LETTER_EXCHANGE, self::BILLING_QUEUE);
        $channel->queue_declare(self::BILLING_QUEUE, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => self::DEAD_LETTER_EXCHANGE,
            'x-dead-letter-routing-key' => self::BILLING_QUEUE,
        ]));
        $channel->queue_bind(self::BILLING_QUEUE, self::EXCHANGE, 'subscription.created.v1');

        return self::$channel = $channel;
    }
}
