<?php

namespace App\Application\Subscription\Commands\ActivateSubscription;

final class ActivateSubscriptionCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $subscriptionId,
    ) {}
}
