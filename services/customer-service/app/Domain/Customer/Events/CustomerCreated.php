<?php

namespace App\Domain\Customer\Events;

use App\Domain\Customer\ValueObjects\CustomerId;
use App\Domain\Customer\ValueObjects\CustomerName;
use App\Domain\Customer\ValueObjects\Email;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class CustomerCreated
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly CustomerId $customerId,
        public readonly MerchantId $merchantId,
        public readonly Email $email,
        public readonly CustomerName $name,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
