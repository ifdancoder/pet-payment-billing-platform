<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Platform\Messaging\ReliableRabbitMqConsumer;

function deliveryMessage(int $attempt): AMQPMessage
{
    $message = new AMQPMessage('{}', ['application_headers' => new AMQPTable([
        'event_id' => 'event-1', 'delivery_attempt' => $attempt,
    ])]);
    $message->setDeliveryInfo(7, false, 'billing.events', 'test.event.v1');
    return $message;
}

test('reliable consumer declares a durable main queue and dead letter queue', function () {
    $channel = Mockery::mock(AMQPChannel::class);
    $channel->shouldReceive('exchange_declare')->once();
    $channel->shouldReceive('queue_declare')->twice();
    $channel->shouldReceive('queue_bind')->twice();
    $channel->shouldReceive('basic_qos')->once()->with(null, 1, null);
    $channel->shouldReceive('confirm_select')->once();
    $channel->shouldReceive('basic_get')->once()->with('test.queue')->andReturn(null);

    expect((new ReliableRabbitMqConsumer)->consumeOne($channel, 'test.queue', 'billing.events', ['test.event.v1'], fn () => null))
        ->toBe('empty');
});

test('a transient failure is confirmed and republished with an incremented attempt', function () {
    $message = deliveryMessage(1);
    $delivery = Mockery::mock(AMQPChannel::class);
    $delivery->shouldReceive('basic_ack')->once()->with(7, false);
    $message->setChannel($delivery);

    $channel = Mockery::mock(AMQPChannel::class)->shouldIgnoreMissing();
    $channel->shouldReceive('basic_get')->once()->andReturn($message);
    $channel->shouldReceive('basic_publish')->once()->withArgs(function (AMQPMessage $retry): bool {
        return $retry->get('application_headers')->getNativeData()['delivery_attempt'] === 2;
    });
    $channel->shouldReceive('wait_for_pending_acks_returns')->once()->with(5.0);

    $result = (new ReliableRabbitMqConsumer)->consumeOne($channel, 'test.queue', 'billing.events', ['test.event.v1'],
        fn () => throw new RuntimeException('temporary'));
    expect($result)->toBe('retried');
});

test('the final failed delivery is rejected into the dead letter queue', function () {
    $message = deliveryMessage(3);
    $delivery = Mockery::mock(AMQPChannel::class);
    $delivery->shouldReceive('basic_reject')->once()->with(7, false);
    $message->setChannel($delivery);

    $channel = Mockery::mock(AMQPChannel::class)->shouldIgnoreMissing();
    $channel->shouldReceive('basic_get')->once()->andReturn($message);

    $result = (new ReliableRabbitMqConsumer)->consumeOne($channel, 'test.queue', 'billing.events', ['test.event.v1'],
        fn () => throw new RuntimeException('poison'));
    expect($result)->toBe('dead-lettered');
});
