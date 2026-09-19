<?php

namespace App\Application\Invoice\Commands\MarkInvoicePaid;

use DateTimeImmutable;

final class MarkInvoicePaidCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $invoiceId,
        public readonly string $merchantId,
        public readonly string $paymentId,
        public readonly DateTimeImmutable $paidAt,
    ) {}
}
