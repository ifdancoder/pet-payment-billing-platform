<?php

namespace App\Domain\Price\Events;

use App\Domain\Price\ValueObjects\BillingInterval;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Product\ValueObjects\ProductId;
use DateTimeImmutable;

final class PriceCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly PriceId $priceId,
        public readonly ProductId $productId,
        public readonly Money $money,
        public readonly BillingInterval $billingInterval,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
