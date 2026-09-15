<?php

namespace App\Domain\Price\Events;

use App\Domain\Price\ValueObjects\PriceId;
use DateTimeImmutable;

final class PriceDeactivated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly PriceId $priceId,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
