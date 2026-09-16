<?php

namespace App\Application\Price\Commands\CreatePrice;

final class CreatePriceCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $productId,
        public readonly int $amountMinorUnits,
        public readonly string $currency,
        public readonly int $type,
        public readonly ?int $billingInterval = null,
        public readonly ?int $billingIntervalCount = null,
    ) {}
}
