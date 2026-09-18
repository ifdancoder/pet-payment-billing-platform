<?php

namespace App\Application\Payment\Commands\CreatePayment;

final class CreatePaymentCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $invoiceId,
        public readonly string $merchantId,
        public readonly string $customerId,
        public readonly int $amountMinorUnits,
        public readonly string $currency,
    ) {}
}
