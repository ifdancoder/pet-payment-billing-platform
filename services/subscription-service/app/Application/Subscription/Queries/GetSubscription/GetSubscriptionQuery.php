<?php

namespace App\Application\Subscription\Queries\GetSubscription;

final class GetSubscriptionQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $id,
    ) {}
}
