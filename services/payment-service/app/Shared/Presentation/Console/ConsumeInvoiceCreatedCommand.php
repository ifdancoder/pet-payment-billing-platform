<?php

namespace App\Shared\Presentation\Console;

use App\Infrastructure\Payment\Adapters\Messaging\Consumers\InvoiceCreatedConsumer;
use App\Shared\Infrastructure\Messaging\RabbitMQ\RabbitMqEventPublisher;
use Illuminate\Console\Command;
use PhpAmqpLib\Channel\AMQPChannel;

final class ConsumeInvoiceCreatedCommand extends Command
{
    private const QUEUE = 'payment.invoice-created';

    protected $signature = 'invoice-created:consume';

    protected $description = 'Drain up to one pending invoice.created.v1 message from the queue and process it.';

    public function handle(AMQPChannel $channel, InvoiceCreatedConsumer $consumer): int
    {
        $channel->queue_declare(self::QUEUE, false, true, false, false);
        $channel->queue_bind(self::QUEUE, RabbitMqEventPublisher::EXCHANGE, 'invoice.created.v1');

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
