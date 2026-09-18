<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Notification\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;

final class ConsumePaymentSucceededCommand extends Command
{
    private const EXCHANGE = 'billing.events';

    private const QUEUE = 'notification.payment-succeeded';

    protected $signature = 'payment-succeeded:consume';

    protected $description = 'Drain up to one pending payment.succeeded.v1 message from the queue and process it.';

    public function handle(AMQPChannel $channel, PaymentSucceededConsumer $consumer): int
    {
        $channel->queue_declare(self::QUEUE, false, true, false, false);
        $channel->queue_bind(self::QUEUE, self::EXCHANGE, 'payment.succeeded.v1');

        $message = $channel->basic_get(self::QUEUE);

        if ($message === null) {
            $this->info('Consumed 0 message(s).');

            return self::SUCCESS;
        }

        $headers = $message->get('application_headers')->getNativeData();
        $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $consumer->handle($headers['event_id'], $payload);

        $message->ack();

        $this->info('Consumed 1 message(s).');

        return self::SUCCESS;
    }
}
