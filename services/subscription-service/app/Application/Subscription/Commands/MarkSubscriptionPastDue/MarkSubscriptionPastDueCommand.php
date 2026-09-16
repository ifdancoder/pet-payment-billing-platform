<?php

namespace App\Application\Subscription\Commands\MarkSubscriptionPastDue;

final class MarkSubscriptionPastDueCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly string $subscriptionId,
    ) {}
}
