<?php

namespace App\Domain\Subscription\Events;

use App\Domain\Subscription\ValueObjects\CustomerId;
use App\Domain\Subscription\ValueObjects\PriceSnapshot;
use App\Domain\Subscription\ValueObjects\SubscriptionId;
use App\Shared\Domain\ValueObjects\MerchantId;
use DateTimeImmutable;

final class SubscriptionRenewalDue
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        public readonly MerchantId $merchantId,
        public readonly CustomerId $customerId,
        public readonly PriceSnapshot $priceSnapshot,
        public readonly DateTimeImmutable $periodStart,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
