<?php

namespace Platform\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

final class ReliableRabbitMqConsumer
{
    public const DEAD_LETTER_EXCHANGE = 'billing.events.dlx';

    public function __construct(private readonly int $maxAttempts = 3) {}

    /**
     * @param array<int, string> $routingKeys
     * @param callable(AMQPMessage, array<string, mixed>, array<string, mixed>): void $handler
     * @return 'empty'|'processed'|'retried'|'dead-lettered'
     */
    public function consumeOne(AMQPChannel $channel, string $queue, string $exchange, array $routingKeys, callable $handler): string
    {
        $this->declareTopology($channel, $queue, $exchange, $routingKeys);
        $message = $channel->basic_get($queue);
        if ($message === null) {
            return 'empty';
        }

        $headers = $message->has('application_headers')
            ? $message->get('application_headers')->getNativeData()
            : [];

        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $handler($message, $headers, $payload);
            $message->ack();
            return 'processed';
        } catch (Throwable $error) {
            $attempt = max(1, (int) ($headers['delivery_attempt'] ?? 1));
            if ($attempt < $this->maxAttempts) {
                $headers['delivery_attempt'] = $attempt + 1;
                $headers['last_error_class'] = $error::class;
                $properties = $message->get_properties();
                $properties['application_headers'] = new AMQPTable($headers);
                $retry = new AMQPMessage($message->getBody(), $properties);

                try {
                    $channel->basic_publish($retry, $exchange, $message->getRoutingKey());
                    $channel->wait_for_pending_acks_returns(5.0);
                    $message->ack();
                    return 'retried';
                } catch (Throwable) {
                    $message->nack(false, true);
                    throw $error;
                }
            }

            $message->reject(false);
            return 'dead-lettered';
        }
    }

    /** @param array<int, string> $routingKeys */
    private function declareTopology(AMQPChannel $channel, string $queue, string $exchange, array $routingKeys): void
    {
        $deadLetterQueue = $queue.'.dlq';
        $channel->exchange_declare(self::DEAD_LETTER_EXCHANGE, 'direct', false, true, false);
        $channel->queue_declare($deadLetterQueue, false, true, false, false);
        $channel->queue_bind($deadLetterQueue, self::DEAD_LETTER_EXCHANGE, $queue);
        $channel->queue_declare($queue, false, true, false, false, false, new AMQPTable([
            'x-dead-letter-exchange' => self::DEAD_LETTER_EXCHANGE,
            'x-dead-letter-routing-key' => $queue,
        ]));
        foreach ($routingKeys as $routingKey) {
            $channel->queue_bind($queue, $exchange, $routingKey);
        }
        $channel->basic_qos(null, 1, null);
        $channel->confirm_select();
    }
}
