<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Payment\Adapters\Messaging\Consumers\InvoiceCreatedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;
use Platform\Messaging\ReliableRabbitMqConsumer;

final class ConsumeInvoiceCreatedCommand extends Command
{
    private const QUEUE = 'payment.invoice-created';

    protected $signature = 'invoice-created:consume';

    protected $description = 'Drain up to one pending invoice.created.v1 message from the queue and process it.';

    public function handle(AMQPChannel $channel, InvoiceCreatedConsumer $consumer, ReliableRabbitMqConsumer $reliable): int
    {
        $result = $reliable->consumeOne($channel, self::QUEUE, RabbitMqEventPublisher::EXCHANGE, ['invoice.created.v1'],
            fn ($message, array $headers, array $payload) => $consumer->handle($headers['event_id'], $payload));
        $this->info($result === 'empty' ? 'Consumed 0 message(s).' : ($result === 'processed' ? 'Consumed 1 message(s).' : "Message {$result}."));

        return self::SUCCESS;
    }
}
