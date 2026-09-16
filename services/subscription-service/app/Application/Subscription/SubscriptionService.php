<?php

namespace App\Application\Subscription;

use App\Application\Subscription\Commands\ActivateSubscription\ActivateSubscriptionCommand;
use App\Application\Subscription\Commands\ActivateSubscription\ActivateSubscriptionHandler;
use App\Application\Subscription\Commands\CancelSubscription\CancelSubscriptionCommand;
use App\Application\Subscription\Commands\CancelSubscription\CancelSubscriptionHandler;
use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionCommand;
use App\Application\Subscription\Commands\CreateSubscription\CreateSubscriptionHandler;
use App\Application\Subscription\Commands\MarkSubscriptionPastDue\MarkSubscriptionPastDueCommand;
use App\Application\Subscription\Commands\MarkSubscriptionPastDue\MarkSubscriptionPastDueHandler;
use App\Application\Subscription\Ports\Inbound\ISubscriptionServicePort;
use App\Application\Subscription\Queries\GetSubscription\GetSubscriptionHandler;
use App\Application\Subscription\Queries\GetSubscription\GetSubscriptionQuery;
use App\Application\Subscription\Queries\ListSubscriptions\ListSubscriptionsHandler;
use App\Application\Subscription\Queries\ListSubscriptions\ListSubscriptionsQuery;
use App\Domain\Subscription\Subscription;

final class SubscriptionService implements ISubscriptionServicePort
{
    public function __construct(
        private readonly CreateSubscriptionHandler $createSubscriptionHandler,
        private readonly GetSubscriptionHandler $getSubscriptionHandler,
        private readonly ListSubscriptionsHandler $listSubscriptionsHandler,
        private readonly ActivateSubscriptionHandler $activateSubscriptionHandler,
        private readonly MarkSubscriptionPastDueHandler $markSubscriptionPastDueHandler,
        private readonly CancelSubscriptionHandler $cancelSubscriptionHandler,
    ) {}

    public function createSubscription(CreateSubscriptionCommand $command): Subscription
    {
        return $this->createSubscriptionHandler->handle($command);
    }

    public function getSubscription(GetSubscriptionQuery $query): Subscription
    {
        return $this->getSubscriptionHandler->handle($query);
    }

    public function listSubscriptions(ListSubscriptionsQuery $query): array
    {
        return $this->listSubscriptionsHandler->handle($query);
    }

    public function activateSubscription(ActivateSubscriptionCommand $command): Subscription
    {
        return $this->activateSubscriptionHandler->handle($command);
    }

    public function markSubscriptionPastDue(MarkSubscriptionPastDueCommand $command): Subscription
    {
        return $this->markSubscriptionPastDueHandler->handle($command);
    }

    public function cancelSubscription(CancelSubscriptionCommand $command): Subscription
    {
        return $this->cancelSubscriptionHandler->handle($command);
    }
}
