<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Invoice\Adapters\Messaging\Consumers\SubscriptionCreatedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use DateTimeImmutable;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;

final class ConsumeSubscriptionCreatedCommand extends Command
{
    private const QUEUE = 'billing.subscription-created';

    protected $signature = 'subscription-created:consume';

    protected $description = 'Drain up to one pending subscription.created.v1 message from the queue and process it.';

    public function handle(AMQPChannel $channel, SubscriptionCreatedConsumer $consumer): int
    {
        $channel->queue_declare(self::QUEUE, false, true, false, false);
        $channel->queue_bind(self::QUEUE, RabbitMqEventPublisher::EXCHANGE, 'subscription.created.v1');

        $message = $channel->basic_get(self::QUEUE);

        if ($message === null) {
            $this->info('Consumed 0 message(s).');

            return self::SUCCESS;
        }

        $headers = $message->get('application_headers')->getNativeData();
        $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $consumer->handle($headers['event_id'], $payload, new DateTimeImmutable($headers['occurred_at']));

        $message->ack();

        $this->info('Consumed 1 message(s).');

        return self::SUCCESS;
    }
}
