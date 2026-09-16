<?php

namespace App\Application\Subscription\Ports\Inbound;

use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Queries\GetSubscription\GetSubscriptionQuery;
use App\Application\Subscription\Queries\ListSubscriptions\ListSubscriptionsQuery;
use App\Domain\Subscription\Subscription;

interface ISubscriptionServicePort
{
    public function createSubscription(CreateSubscriptionCommand $command): Subscription;

    public function getSubscription(GetSubscriptionQuery $query): Subscription;

    /**
     * @return array<int, Subscription>
     */
    public function listSubscriptions(ListSubscriptionsQuery $query): array;
}
