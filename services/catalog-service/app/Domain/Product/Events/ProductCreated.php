<?php

namespace App\Domain\Product\Events;

use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Product\ValueObjects\ProductName;
use DateTimeImmutable;

final class ProductCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly ProductId $productId,
        public readonly ProductName $name,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
