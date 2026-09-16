<?php

namespace App\Domain\Subscription\Events;

use App\Domain\Subscription\ValueObjects\SubscriptionId;
use DateTimeImmutable;

final class SubscriptionMarkedPastDue
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct(
        public readonly SubscriptionId $subscriptionId,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable;
    }
}
