<?php

namespace App\Infrastructure\Invoice\Adapters\Messaging\Consumers;

use App\Application\Invoice\Commands\MarkInvoicePaid\MarkInvoicePaidCommand;
use App\Application\Invoice\Commands\MarkInvoicePaid\MarkInvoicePaidHandler;
use DateTimeImmutable;

final class PaymentSucceededConsumer
{
    public function __construct(private readonly MarkInvoicePaidHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $this->handler->handle(new MarkInvoicePaidCommand(
            $eventId,
            'payment.succeeded.v1',
            $payload['invoice_id'],
            $payload['merchant_id'],
            $payload['payment_id'],
            new DateTimeImmutable($payload['paid_at']),
        ));
    }
}
