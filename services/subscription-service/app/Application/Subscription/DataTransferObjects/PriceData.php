<?php

namespace App\Application\Subscription\DataTransferObjects;

/**
 * What CreateSubscription needs to know about a catalog price, translated
 * from catalog-service's own wire format. Never the catalog-service Price
 * domain object itself — this is the anti-corruption boundary.
 */
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
