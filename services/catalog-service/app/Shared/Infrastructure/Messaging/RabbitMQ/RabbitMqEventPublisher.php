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
        $headers = [
            'event_id' => $message->eventId,
            'aggregate_type' => $message->aggregateType,
            'aggregate_id' => $message->aggregateId,
            'occurred_at' => $message->occurredAt->format(DATE_ATOM),
            'correlation_id' => $message->payload['correlation_id'] ?? $message->eventId,
            'causation_id' => $message->payload['causation_id'] ?? $message->eventId,
        ];
        $amqpMessage = new AMQPMessage(json_encode($message->payload, JSON_THROW_ON_ERROR), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $message->eventId, 'correlation_id' => $headers['correlation_id'],
            'timestamp' => $message->occurredAt->getTimestamp(), 'application_headers' => new AMQPTable($headers),
        ]);

        $this->channel->confirm_select();
        $this->channel->basic_publish($amqpMessage, self::EXCHANGE, $message->eventType);
        $this->channel->wait_for_pending_acks_returns(5.0);
    }
}
