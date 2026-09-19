<?php

namespace App\Infrastructure\Subscription\Adapters\Messaging\Consumers;

use App\Application\Subscription\Commands\HandleInvoicePaymentFailed\HandleInvoicePaymentFailedCommand;
use App\Application\Subscription\Commands\HandleInvoicePaymentFailed\HandleInvoicePaymentFailedHandler;

final class InvoicePaymentFailedConsumer
{
    public function __construct(private readonly HandleInvoicePaymentFailedHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $this->handler->handle(new HandleInvoicePaymentFailedCommand(
            $eventId,
            'invoice.payment_failed.v1',
            $payload['subscription_id'],
            $payload['merchant_id'],
        ));
    }
}
