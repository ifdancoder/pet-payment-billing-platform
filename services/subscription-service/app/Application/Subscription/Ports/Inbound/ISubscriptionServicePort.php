<?php

namespace App\Application\Subscription\Ports\Inbound;

use App\Application\Subscription\Commands\ActivateSubscription\ActivateSubscriptionCommand;
use App\Application\Subscription\Commands\CancelSubscription\CancelSubscriptionCommand;
use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Commands\MarkSubscriptionPastDue\MarkSubscriptionPastDueCommand;
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

    public function activateSubscription(ActivateSubscriptionCommand $command): Subscription;

    public function markSubscriptionPastDue(MarkSubscriptionPastDueCommand $command): Subscription;

    public function cancelSubscription(CancelSubscriptionCommand $command): Subscription;
}
