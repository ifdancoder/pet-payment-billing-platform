<?php

namespace App\Application\Subscription\Commands\CancelSubscription;

final class CancelSubscriptionCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $subscriptionId,
    ) {}
}
