<?php

namespace App\Domain\Price\Events;

use App\Domain\Price\ValueObjects\BillingPeriod;
use App\Domain\Price\ValueObjects\Money;
use App\Domain\Price\ValueObjects\PriceId;
use App\Domain\Price\ValueObjects\PriceType;
use App\Domain\Product\ValueObjects\ProductId;
use DateTimeImmutable;

final class PriceCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly PriceId $priceId,
        public readonly ProductId $productId,
        public readonly Money $money,
        public readonly PriceType $type,
        public readonly ?BillingPeriod $billingPeriod,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
