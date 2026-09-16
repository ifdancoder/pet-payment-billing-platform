<?php

namespace App\Application\Subscription\Commands\CreateSubscription;

final class CreateSubscriptionCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $customerId,
        public readonly string $priceId,
    ) {}
}
