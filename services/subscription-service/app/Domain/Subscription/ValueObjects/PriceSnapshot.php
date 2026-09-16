<?php

namespace App\Domain\Subscription\ValueObjects;

/**
 * An immutable copy of the Catalog price data a Subscription was created
 * with. Captured once at creation time so that later changes to the price
 * in catalog-service (a repricing, a currency change, ...) never retroactively
 * alter an existing subscription.
 */
final class PriceSnapshot
{
    private function __construct(
        private readonly PriceId $priceId,
        private readonly ProductId $productId,
        private readonly Money $money,
        private readonly BillingPeriod $billingPeriod,
    ) {}

    public static function of(PriceId $priceId, ProductId $productId, Money $money, BillingPeriod $billingPeriod): self
    {
        return new self($priceId, $productId, $money, $billingPeriod);
    }

    public function priceId(): PriceId
    {
        return $this->priceId;
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function money(): Money
    {
        return $this->money;
    }

    public function billingPeriod(): BillingPeriod
    {
        return $this->billingPeriod;
    }

    public function equals(self $other): bool
    {
        return $this->priceId->equals($other->priceId)
            && $this->productId->equals($other->productId)
            && $this->money->equals($other->money)
            && $this->billingPeriod->equals($other->billingPeriod);
    }
}
