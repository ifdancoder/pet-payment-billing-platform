<?php

namespace App\Application\Subscription\DataTransferObjects;

final class PriceData
{
    public function __construct(
        public readonly string $priceId,
        public readonly string $productId,
        public readonly int $amountMinorUnits,
        public readonly string $currency,
        public readonly string $type,
        public readonly ?string $billingInterval,
        public readonly ?int $billingIntervalCount,
        public readonly string $status,
    ) {}

    public function isRecurring(): bool
    {
        return $this->type === 'recurring';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
