<?php

namespace App\Domain\Merchant\Events;

use App\Domain\Merchant\ValueObjects\MerchantId;
use DateTimeImmutable;

final class MerchantDisabled
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly MerchantId $merchantId,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
