<?php

namespace App\Domain\Merchant\Events;

use App\Domain\Merchant\ValueObjects\MerchantId;
use App\Domain\Merchant\ValueObjects\MerchantName;
use DateTimeImmutable;

final class MerchantCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly MerchantId $merchantId,
        public readonly MerchantName $name,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
