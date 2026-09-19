<?php

namespace App\Application\Invoice\Commands\RelayPaymentFailed;

final class RelayPaymentFailedCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $invoiceId,
        public readonly string $merchantId,
        public readonly string $paymentId,
        public readonly string $failureCode,
    ) {}
}
