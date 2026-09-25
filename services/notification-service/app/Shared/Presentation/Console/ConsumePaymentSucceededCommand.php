<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Notification\Adapters\Messaging\Consumers\PaymentSucceededConsumer;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use Platform\Messaging\ReliableRabbitMqConsumer;

final class ConsumePaymentSucceededCommand extends Command
{
    private const EXCHANGE = 'billing.events';

    private const QUEUE = 'notification.payment-succeeded';

    protected $signature = 'payment-succeeded:consume';

    protected $description = 'Drain up to one pending payment.succeeded.v1 message from the queue and process it.';

    public function handle(AMQPChannel $channel, PaymentSucceededConsumer $consumer, ReliableRabbitMqConsumer $reliable): int
    {
        $result = $reliable->consumeOne($channel, self::QUEUE, self::EXCHANGE, ['payment.succeeded.v1'],
            fn ($message, array $headers, array $payload) => $consumer->handle($headers['event_id'], $payload));
        $this->info($result === 'empty' ? 'Consumed 0 message(s).' : ($result === 'processed' ? 'Consumed 1 message(s).' : "Message {$result}."));

        return self::SUCCESS;
    }
}
