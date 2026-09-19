<?php

namespace App\Infrastructure\Invoice\Adapters\Messaging\Consumers;

use App\Application\Invoice\Commands\RelayPaymentFailed\RelayPaymentFailedCommand;
use App\Application\Invoice\Commands\RelayPaymentFailed\RelayPaymentFailedHandler;

final class PaymentFailedConsumer
{
    public function __construct(private readonly RelayPaymentFailedHandler $handler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        $this->handler->handle(new RelayPaymentFailedCommand(
            $eventId,
            'payment.failed.v1',
            $payload['invoice_id'],
            $payload['merchant_id'],
            $payload['payment_id'],
            $payload['failure_code'],
        ));
    }
}
