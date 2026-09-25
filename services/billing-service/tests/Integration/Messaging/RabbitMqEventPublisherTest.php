<?php

use App\Shared\Application\ReadModels\OutboxMessage;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

test('publish sends the message to the billing.events exchange with the event type as routing key', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('confirm_select')->once();
    $channel->shouldReceive('wait_for_pending_acks_returns')->once()->with(5.0);
    $message = new OutboxMessage(
        '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'invoice.created.v1',
        'invoice',
        '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        ['status' => 'open'],
        new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
    );

    $channel->shouldReceive('basic_publish')
        ->once()
        ->withArgs(function (AMQPMessage $amqpMessage, string $exchange, string $routingKey) use ($message) {
            $headers = $amqpMessage->get('application_headers')->getNativeData();

            return $exchange === 'billing.events'
                && $routingKey === 'invoice.created.v1'
                && json_decode($amqpMessage->getBody(), true) === $message->payload
                && $headers['event_id'] === $message->eventId
                && $headers['aggregate_type'] === $message->aggregateType
                && $headers['aggregate_id'] === $message->aggregateId
                && $headers['occurred_at'] === $message->occurredAt->format(DATE_ATOM);
        });

    (new RabbitMqEventPublisher($channel))->publish($message);
});

test('publish marks the message persistent and JSON content type', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('confirm_select')->once();
    $channel->shouldReceive('wait_for_pending_acks_returns')->once()->with(5.0);
    $message = new OutboxMessage(
        '9f8e7d6c-5b4a-4321-9876-abcdef012345',
        'invoice.created.v1',
        'invoice',
        '1a2b3c4d-5e6f-4321-8765-0123456789ab',
        ['status' => 'open'],
        new DateTimeImmutable,
    );

    $channel->shouldReceive('basic_publish')
        ->once()
        ->withArgs(fn (AMQPMessage $amqpMessage) => $amqpMessage->get('delivery_mode') === AMQPMessage::DELIVERY_MODE_PERSISTENT
            && $amqpMessage->get('content_type') === 'application/json');

    (new RabbitMqEventPublisher($channel))->publish($message);
});
