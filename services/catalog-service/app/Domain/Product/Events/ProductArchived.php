<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\ValueObjects\ProductId;
use DateTimeImmutable;

final class ProductArchived
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly ProductId $productId,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
