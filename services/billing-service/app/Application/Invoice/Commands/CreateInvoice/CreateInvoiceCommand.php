<?php

namespace App\Application\Invoice\Commands\CreateInvoice;

use DateTimeImmutable;

final class CreateInvoiceCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly string $merchantId,
        public readonly string $customerId,
        public readonly string $subscriptionId,
        public readonly ?string $productId,
        public readonly ?string $priceId,
        public readonly string $description,
        public readonly int $amountMinorUnits,
        public readonly string $currency,
        public readonly string $billingInterval,
        public readonly int $billingIntervalCount,
        public readonly DateTimeImmutable $periodStart,
        public readonly bool $renewal = false,
    ) {}
}
