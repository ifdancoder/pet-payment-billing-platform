<?php

namespace App\Application\Subscription\Queries\ListSubscriptions;

final class ListSubscriptionsQuery
{
    public function __construct(public readonly string $merchantId) {}
}
