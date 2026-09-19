<?php

namespace App\Shared\Infrastructure\Messaging\RabbitMQ;

use App\Shared\Application\Ports\Outbound\IEventPublisherPort;
use App\Shared\Application\ReadModels\OutboxMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class RabbitMqEventPublisher implements IEventPublisherPort
{
    public const EXCHANGE = 'billing.events';

    public function __construct(private readonly AMQPChannel $channel) {}

    public function publish(OutboxMessage $message): void
    {
        $amqpMessage = new AMQPMessage(json_encode($message->payload, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable([
                'event_id' => $message->eventId,
                'aggregate_type' => $message->aggregateType,
                'aggregate_id' => $message->aggregateId,
                'occurred_at' => $message->occurredAt->format(DATE_ATOM),
            ]),
        ]);

        $this->channel->basic_publish($amqpMessage, self::EXCHANGE, $message->eventType);
    }
}
